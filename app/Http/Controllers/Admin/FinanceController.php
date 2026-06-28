<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingPaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundBookingRequest;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Booking;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Payout;
use App\Services\AuditLogService;
use App\Services\PaymentService;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlementService,
        private readonly PaymentService $paymentService,
    ) {}

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
        $operator = Operator::find($request->input('commission_id'));

        if (! $operator) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhà xe cần quyết toán',
                'code' => 'OPERATOR_NOT_FOUND',
            ], 404);
        }

        $rate = (float) $operator->commission_rate;
        $adminId = auth()->id();

        // Atomic + khóa nhà xe để không chi trùng khi bấm 2 lần / 2 admin đồng thời (R4).
        $result = DB::transaction(function () use ($operator, $rate, $adminId) {
            DB::table('operators')->where('id', $operator->id)->lockForUpdate()->first();

            $settlement = $this->settlementService->forOperator($operator->id, $rate)['settlement'];
            if ($settlement < 0) {
                return ['code' => 'OPERATOR_OWES_PLATFORM', 'owed' => -$settlement];
            }

            $paid = (int) Payout::where('operator_id', $operator->id)->where('status', 'paid')->sum('amount');
            $outstanding = $settlement - $paid;
            if ($outstanding <= 0) {
                return ['code' => 'NOTHING_TO_SETTLE'];
            }

            // Chi TOÀN BỘ phần còn nợ: gộp (supersede) mọi yêu cầu pending của nhà xe rồi
            // ghi đúng 1 bản ghi 'paid' = outstanding → không chi trùng, không để pending mồ côi.
            Payout::where('operator_id', $operator->id)->where('status', 'pending')->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'processed_by' => $adminId,
                'note' => 'Gộp vào đợt quyết toán toàn bộ bởi admin',
            ]);

            return ['payout' => Payout::create([
                'operator_id' => $operator->id,
                'amount' => $outstanding,
                'status' => 'paid',
                'note' => 'Quyết toán bởi admin',
                'requested_at' => now(),
                'processed_at' => now(),
                'processed_by' => $adminId,
            ])];
        });

        if (($result['code'] ?? null) === 'OPERATOR_OWES_PLATFORM') {
            return response()->json([
                'success' => false,
                'message' => 'Nhà xe đang NỢ nền tảng '.number_format($result['owed'], 0, ',', '.').'đ (hoa hồng vé tiền mặt), không thể chi quyết toán',
                'code' => 'OPERATOR_OWES_PLATFORM',
            ], 422);
        }

        if (($result['code'] ?? null) === 'NOTHING_TO_SETTLE') {
            return response()->json([
                'success' => false,
                'message' => 'Nhà xe này không có số dư cần quyết toán',
                'code' => 'NOTHING_TO_SETTLE',
            ], 422);
        }

        $payout = $result['payout'];

        app(AuditLogService::class)->log(
            action: 'payout_operator',
            model: $operator,
            description: "Đã quyết toán thành công " . number_format($payout->amount, 0, ',', '.') . "đ cho nhà xe: {$operator->company_name}",
            newValues: $payout->toArray()
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã quyết toán '.number_format($payout->amount, 0, ',', '.').'đ cho '.$operator->company_name,
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
        $payments = Payment::with(['booking.user', 'booking.trip.route.operator'])
            ->when($request->input('method'), fn ($q) => $q->where('method', $request->input('method')))
            ->when($request->input('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('paid_at')
            ->paginate(30);

        $mapped = collect($payments->items())->map(function (Payment $payment) {
            $operatorName = 'N/A';
            if ($payment->booking && $payment->booking->trip) {
                if ($payment->booking->trip->route && $payment->booking->trip->route->operator) {
                    $operatorName = $payment->booking->trip->route->operator->company_name;
                } elseif ($payment->booking->trip->driver && $payment->booking->trip->driver->operator) {
                    $operatorName = $payment->booking->trip->driver->operator->company_name;
                }
            }

            return [
                'id' => $payment->id,
                'booking_id' => $payment->booking?->id,
                'type' => $payment->status->value === 'refunded' ? 'refund' : 'booking',
                'amount' => $payment->amount,
                'booking_code' => $payment->booking ? $payment->booking->booking_code : 'N/A',
                'customer' => $payment->booking && $payment->booking->user ? $payment->booking->user->full_name : ($payment->user ? $payment->user->full_name : 'N/A'),
                'operator' => $operatorName,
                'method' => $payment->method ? $payment->method->value : '',
                'status' => $payment->status ? $payment->status->value : '',
                'created_at' => $payment->paid_at ? $payment->paid_at->toIso8601String() : $payment->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
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

    /**
     * Hoàn tiền thủ công cho 1 vé (PRD F-A03). Tái dùng PaymentService::refund:
     * vé online → hoàn về ví khách; vé tiền mặt → chỉ đánh dấu để đối soát.
     */
    public function refund(RefundBookingRequest $request, string $booking): JsonResponse
    {
        $bookingModel = Booking::with(['payment', 'user'])->find($booking);

        if (! $bookingModel) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy vé', 'code' => 'BOOKING_NOT_FOUND'], 404);
        }

        if ($bookingModel->payment_status !== BookingPaymentStatus::Paid) {
            return response()->json(['success' => false, 'message' => 'Chỉ hoàn tiền được vé đã thanh toán', 'code' => 'BOOKING_NOT_PAID'], 422);
        }

        $amount = (int) $request->validated('amount');
        $final = (int) $bookingModel->final_amount;
        if ($amount > $final) {
            return response()->json([
                'success' => false,
                'message' => 'Số tiền hoàn ('.number_format($amount, 0, ',', '.').'đ) vượt quá giá trị vé ('.number_format($final, 0, ',', '.').'đ)',
                'code' => 'REFUND_EXCEEDS_TOTAL',
            ], 422);
        }

        $reason = (string) $request->validated('reason');

        // Hoàn tiền trong transaction (PaymentService) → đánh dấu payment/booking refunded.
        $this->paymentService->refund($bookingModel, $amount);

        // Thông báo khách qua SMS (môi trường dev: hiện ở terminal).
        SendSmsNotificationJob::dispatch(
            $bookingModel->contact_phone,
            "[XeGhep] Vé {$bookingModel->booking_code} đã được hoàn ".number_format($amount, 0, ',', '.')."đ. Lý do: {$reason}",
            $bookingModel->id,
        )->onQueue('notifications');

        app(AuditLogService::class)->log(
            action: 'refund_booking',
            model: $bookingModel,
            description: 'Đã hoàn '.number_format($amount, 0, ',', '.')."đ cho vé {$bookingModel->booking_code}. Lý do: {$reason}",
            newValues: ['amount' => $amount, 'reason' => $reason]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã hoàn '.number_format($amount, 0, ',', '.').'đ cho vé '.$bookingModel->booking_code,
            'data' => ['amount' => $amount],
        ]);
    }
}
