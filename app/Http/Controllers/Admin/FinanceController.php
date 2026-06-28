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
use App\Models\Trip;
use App\Services\AuditLogService;
use App\Services\PaymentService;
use App\Services\SettlementService;
use Carbon\Carbon;
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
            ->when($request->input('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->input('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->when($request->input('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->whereHas('booking', function ($b) use ($search) {
                    $b->where('booking_code', 'LIKE', "%{$search}%")
                        ->orWhere('contact_phone', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('full_name', 'LIKE', "%{$search}%"));
                });
            })
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
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
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

    /**
     * Phát hiện giao dịch bất thường (PRD F-A03): cùng SĐT liên hệ đặt nhiều vé
     * (ngưỡng mặc định 3). Dùng để admin rà soát gian lận/spam.
     */
    public function anomalies(Request $request): JsonResponse
    {
        $threshold = max(2, (int) $request->input('min_bookings', 3));

        $rows = Booking::query()
            ->selectRaw('contact_phone, MAX(contact_name) as contact_name, COUNT(*) as booking_count, SUM(final_amount) as total_amount')
            ->groupBy('contact_phone')
            ->having('booking_count', '>=', $threshold)
            ->orderByDesc('booking_count')
            ->limit(50)
            ->get()
            ->map(fn ($r) => [
                'contact_phone' => $r->contact_phone,
                'contact_name' => $r->contact_name,
                'booking_count' => (int) $r->booking_count,
                'total_amount' => (int) $r->total_amount,
            ]);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /** Xuất báo cáo CSV (không cần package): type=transactions|commissions. */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $type = $request->input('type') === 'commissions' ? 'commissions' : 'transactions';

        if ($type === 'commissions') {
            $rows = $this->operatorSettlements()
                ->map(fn ($r) => [
                    $r['operator_name'], $r['revenue'], $r['commission'],
                    $r['net_amount'], $r['status'],
                ]);
            $header = ['Nhà xe', 'Doanh thu', 'Hoa hồng', 'Còn lại (net)', 'Trạng thái'];
            $filename = 'quyet-toan-'.now()->format('Ymd-His').'.csv';
        } else {
            $rows = Payment::with(['booking'])->latest('paid_at')->limit(5000)->get()
                ->map(fn (Payment $p) => [
                    $p->booking?->booking_code ?? 'N/A',
                    (int) $p->amount,
                    $p->method?->value ?? '',
                    $p->status?->value ?? '',
                    optional($p->paid_at ?? $p->created_at)->format('Y-m-d H:i'),
                ]);
            $header = ['Mã vé', 'Số tiền', 'Phương thức', 'Trạng thái', 'Thời gian'];
            $filename = 'giao-dich-'.now()->format('Ymd-His').'.csv';
        }

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM để Excel đọc UTF-8
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Lịch sử các đợt quyết toán đã chi cho nhà xe (Payout status=paid). */
    public function payouts(Request $request): JsonResponse
    {
        $payouts = Payout::with('operator')
            ->where('status', 'paid')
            ->latest('processed_at')
            ->paginate(20);

        $mapped = collect($payouts->items())->map(fn (Payout $p) => [
            'id' => $p->id,
            'operator_name' => $p->operator?->company_name ?? 'N/A',
            'amount' => (int) $p->amount,
            'note' => $p->note,
            'processed_at' => $p->processed_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $mapped,
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    /**
     * Báo cáo doanh thu nền tảng theo kỳ (P3). Trục thời gian = giờ chạy chuyến (depart_at).
     * KHÔNG đụng settlement/payout (vốn tích lũy toàn thời gian). Tái dùng
     * SettlementService::forOperator(..., $tripIds) → hoa hồng khớp tuyệt đối quyết toán.
     */
    public function revenue(Request $request): JsonResponse
    {
        [$from, $to, $period] = $this->resolvePeriod($request);

        // Tổng nền tảng + theo nhà xe (lặp operator, lọc chuyến theo kỳ qua depart_at).
        $gmv = 0;
        $commission = 0;
        $cashCollected = 0;
        $byOperator = [];

        foreach (Operator::all() as $op) {
            $tripIds = Trip::whereHas('vehicle', fn ($q) => $q->where('operator_id', $op->id))
                ->whereBetween('depart_at', [$from, $to])
                ->pluck('id');
            if ($tripIds->isEmpty()) {
                continue;
            }
            $s = $this->settlementService->forOperator($op->id, (float) $op->commission_rate, $tripIds);
            if ($s['gross'] <= 0) {
                continue;
            }
            $gmv += $s['gross'];
            $commission += $s['commission'];
            $cashCollected += $s['cash_gross'];
            $byOperator[] = [
                'operator_name' => $op->company_name,
                'revenue' => $s['gross'],
                'commission' => $s['commission'],
            ];
        }
        usort($byOperator, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Tập chuyến trong kỳ (platform-wide) cho chỉ số đếm + biểu đồ theo ngày.
        $periodTripIds = Trip::whereBetween('depart_at', [$from, $to])->pluck('id');
        $realized = Booking::whereIn('trip_id', $periodTripIds)
            ->where('booking_status', 'completed')
            ->where('payment_status', 'paid');

        $totalTrips = (int) Trip::whereIn('id', $periodTripIds)->where('status', 'completed')->count();
        $totalBookings = (int) (clone $realized)->count();
        $totalPassengers = (int) (clone $realized)->sum('passenger_count');
        $totalRefunds = (int) Payment::where('status', 'refunded')
            ->whereBetween('refunded_at', [$from, $to])
            ->sum('refund_amount');

        // Doanh thu theo NGÀY CHẠY (biểu đồ).
        $daily = (clone $realized)
            ->join('trips', 'bookings.trip_id', '=', 'trips.id')
            ->selectRaw('DATE(trips.depart_at) as date, COUNT(*) as total_bookings, SUM(bookings.final_amount) as revenue')
            ->groupByRaw('DATE(trips.depart_at)')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'total_bookings' => (int) $r->total_bookings,
                'revenue' => (int) $r->revenue,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'summary' => [
                    'gmv' => $gmv,
                    'commission' => $commission,
                    'cash_collected' => $cashCollected,
                    'platform_held' => $gmv - $cashCollected,
                    'total_trips' => $totalTrips,
                    'total_bookings' => $totalBookings,
                    'total_passengers' => $totalPassengers,
                    'total_refunds' => $totalRefunds,
                ],
                'by_operator' => $byOperator,
                'daily' => $daily,
            ],
        ]);
    }

    /** Giải kỳ today/week/month/custom → [from, to, label] (mirror Operator/RevenueController). */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'month');

        [$from, $to] = match ($period) {
            'today' => [today(), today()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->input('from_date') ?: 'today')->startOfDay(),
                Carbon::parse($request->input('to_date') ?: 'today')->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        return [$from, $to, $period];
    }
}
