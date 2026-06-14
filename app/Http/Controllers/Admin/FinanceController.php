<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Payout;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FinanceController extends Controller
{
    public function __construct(private readonly SettlementService $settlementService) {}

    /** Tổng quan tài chính nền tảng (toàn thời gian) cho Admin */
    public function summary(Request $request): JsonResponse
    {
        $settlements = $this->operatorSettlements();

        // GMV = toàn bộ doanh thu realized; trong đó chỉ phần ONLINE là tiền nền tảng
        // thực giữ, phần tiền mặt do tài xế thu (ngoài nền tảng).
        $gmv = (int) $settlements->sum('revenue');
        $cashCollected = (int) $settlements->sum('cash_gross');
        $platformHeld = $gmv - $cashCollected;

        $totalCommission = (int) $settlements->sum('commission');
        $pendingSettlement = (int) $settlements->sum(fn ($r) => max(0, $r['net_amount']));
        $operatorDebt = (int) $settlements->sum(fn ($r) => max(0, -$r['net_amount']));
        $totalRefunds = (int) Payment::where('status', 'refunded')->sum('refund_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_revenue' => $gmv,
                    'platform_held' => $platformHeld,
                    'cash_collected' => $cashCollected,
                    'total_commission' => $totalCommission,
                    'pending_settlement' => $pendingSettlement,
                    'operator_debt' => $operatorDebt,
                    'total_refunds' => $totalRefunds,
                ],
            ],
        ]);
    }

    /** Quyết toán hoa hồng theo từng nhà xe (toàn thời gian) */
    public function commissions(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->operatorSettlements()->values(),
        ]);
    }

    /** Admin thực hiện quyết toán (chi tiền) cho 1 nhà xe */
    public function payout(Request $request): JsonResponse
    {
        $operatorId = $request->input('commission_id');

        $settlement = $this->operatorSettlements()->firstWhere('id', $operatorId);

        if (! $settlement) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà xe cần quyết toán',
                'code' => 'OPERATOR_NOT_FOUND',
            ], 404);
        }

        if ($settlement['net_amount'] < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nhà xe đang NỢ nền tảng '.number_format(-$settlement['net_amount'], 0, ',', '.').'đ (hoa hồng vé tiền mặt), không thể chi quyết toán',
                'code' => 'OPERATOR_OWES_PLATFORM',
            ], 422);
        }

        if ($settlement['net_amount'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nhà xe này không có số dư cần quyết toán',
                'code' => 'NOTHING_TO_SETTLE',
            ], 422);
        }

        $payout = Payout::create([
            'operator_id' => $operatorId,
            'amount' => $settlement['net_amount'],
            'status' => 'paid',
            'note' => 'Quyết toán bởi admin',
            'requested_at' => now(),
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã quyết toán '.number_format($payout->amount, 0, ',', '.').'đ cho '.$settlement['operator_name'],
            'data' => ['amount' => (int) $payout->amount],
        ], 201);
    }

    /**
     * Tính quyết toán theo từng nhà xe (toàn thời gian), đối soát online vs tiền mặt
     * qua SettlementService — đồng bộ tuyệt đối với portal Operator.
     *
     * net_amount > 0 ⇒ nền tảng còn nợ nhà xe (pending → có thể chi).
     * net_amount < 0 ⇒ nhà xe nợ nền tảng hoa hồng tiền mặt (receivable).
     */
    private function operatorSettlements(): Collection
    {
        return Operator::query()
            ->get()
            ->map(function (Operator $op) {
                $rate = (float) $op->commission_rate;
                $s = $this->settlementService->forOperator($op->id, $rate);

                $paidOut = (int) Payout::where('operator_id', $op->id)
                    ->where('status', 'paid')
                    ->sum('amount');
                $outstanding = $s['settlement'] - $paidOut;

                $status = $outstanding > 0 ? 'pending' : ($outstanding < 0 ? 'receivable' : 'paid');

                return [
                    'id' => $op->id,
                    'operator_name' => $op->company_name,
                    'period' => 'Toàn thời gian',
                    'revenue' => $s['gross'],
                    'cash_gross' => $s['cash_gross'],
                    'commission_rate' => $rate,
                    'commission' => $s['commission'],
                    'cash_commission' => $s['cash_commission'],
                    'net_amount' => $outstanding,
                    'status' => $status,
                    'bank_info' => trim(($op->bank_name ? $op->bank_name.' - ' : '').($op->bank_account ?? '')),
                ];
            })
            ->filter(fn ($r) => $r['revenue'] > 0)
            ->values();
    }

    public function transactions(Request $request): JsonResponse
    {
        $payments = Payment::with(['booking.user', 'booking.trip.route'])
            ->when($request->method, fn ($q) => $q->where('method', $request->method))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('paid_at')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => ['current_page' => $payments->currentPage(), 'total' => $payments->total()],
        ]);
    }

    public function refunds(Request $request): JsonResponse
    {
        $refunds = Payment::where('status', 'refunded')
            ->with(['booking.user'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $refunds->items(),
            'meta' => ['current_page' => $refunds->currentPage(), 'total' => $refunds->total()],
        ]);
    }
}
