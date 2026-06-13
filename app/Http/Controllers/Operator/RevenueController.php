<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use App\Models\Trip;
use App\Services\SettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    /** Trạng thái vé tính là doanh thu THỰC NHẬN (đã hoàn tất + đã thanh toán) */
    private const REALIZED = ['booking_status' => 'completed', 'payment_status' => 'paid'];

    public function __construct(private readonly SettlementService $settlementService) {}

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Giải kỳ today/week/month/custom → [from, to, label] */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'month');

        [$from, $to] = match ($period) {
            'today' => [today(), today()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->from_date),
                Carbon::parse($request->to_date)->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        return [$from, $to, $period];
    }

    /** ID các chuyến của nhà xe theo depart_at trong kỳ (null = mọi thời gian) */
    private function operatorTripIds(string $operatorId, $from = null, $to = null)
    {
        return Trip::whereHas('vehicle', fn ($q) => $q->where('operator_id', $operatorId))
            ->when($from && $to, fn ($q) => $q->whereBetween('depart_at', [$from, $to]))
            ->pluck('id');
    }

    /** Query vé doanh thu thực nhận trên tập chuyến */
    private function realizedBookings($tripIds)
    {
        return Booking::whereIn('trip_id', $tripIds)
            ->where('booking_status', self::REALIZED['booking_status'])
            ->where('payment_status', self::REALIZED['payment_status']);
    }

    // ── Endpoints ──────────────────────────────────────────────────────────

    public function summary(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to, $period] = $this->resolvePeriod($request);
        $rate = (float) $operator->commission_rate;

        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        $grossRevenue = (int) $this->realizedBookings($tripIds)->sum('final_amount');
        $commission = (int) round($grossRevenue * $rate / 100);
        $netRevenue = $grossRevenue - $commission;
        $totalBookings = (int) $this->realizedBookings($tripIds)->count();

        // Chuyến đã hoàn thành trong kỳ
        $completedTrips = Trip::whereIn('id', $tripIds)->where('status', 'completed')
            ->with('vehicle:id,seat_count')->get();
        $totalTrips = $completedTrips->count();

        // Tỷ lệ lấp đầy THẬT = Σ khách / Σ ghế (cộng ghế THEO TỪNG CHUYẾN — 1 xe chạy
        // nhiều chuyến thì mỗi chuyến là một lượt sức chứa riêng) × 100
        $totalSeats = (int) $completedTrips->sum(fn ($t) => $t->vehicle->seat_count ?? 0);
        $totalPax = (int) $this->realizedBookings($completedTrips->pluck('id'))->sum('passenger_count');
        $occupancy = $totalSeats > 0 ? round($totalPax / $totalSeats * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'total_trips' => $totalTrips,
                'total_bookings' => $totalBookings,
                'gross_revenue' => $grossRevenue,
                'commission' => $commission,
                'commission_rate' => $rate,
                'net_revenue' => $netRevenue,
                'avg_occupancy' => $occupancy,
            ],
        ]);
    }

    public function daily(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        // Nhóm theo NGÀY CHẠY (trips.depart_at) — đồng bộ với summary
        $daily = $this->realizedBookings($tripIds)
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

        return response()->json(['success' => true, 'data' => $daily]);
    }

    public function byRoute(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        $rows = $this->realizedBookings($tripIds)
            ->join('trips', 'bookings.trip_id', '=', 'trips.id')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->selectRaw('routes.id as route_id, routes.origin_city, routes.dest_city,
                         COUNT(*) as total_bookings, SUM(bookings.final_amount) as revenue')
            ->groupBy('routes.id', 'routes.origin_city', 'routes.dest_city')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => [
                'name' => "{$r->origin_city} → {$r->dest_city}",
                'total_bookings' => (int) $r->total_bookings,
                'revenue' => (int) $r->revenue,
            ]);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function byDriver(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        $rows = $this->realizedBookings($tripIds)
            ->join('trips', 'bookings.trip_id', '=', 'trips.id')
            ->join('drivers', 'trips.driver_id', '=', 'drivers.id')
            ->join('users', 'drivers.user_id', '=', 'users.id')
            ->selectRaw('users.full_name as name, COUNT(*) as total_bookings, SUM(bookings.final_amount) as revenue')
            ->groupBy('drivers.id', 'users.full_name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'total_bookings' => (int) $r->total_bookings,
                'revenue' => (int) $r->revenue,
            ]);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Số dư khả dụng để quyết toán = settlement (đã trừ hoa hồng tiền mặt nhà xe đang
     * nợ nền tảng) − các yêu cầu đã gửi (chưa bị từ chối).
     */
    private function availableBalance(string $operatorId, float $rate): array
    {
        $s = $this->settlementService->forOperator($operatorId, $rate);
        $settlement = $s['settlement'];
        $requested = (int) Payout::where('operator_id', $operatorId)->where('status', '!=', 'rejected')->sum('amount');
        $available = max(0, $settlement - $requested);

        return [
            'total_net' => $settlement,
            'cash_commission' => $s['cash_commission'],
            'requested' => $requested,
            'available' => $available,
        ];
    }

    public function payouts(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $rate = (float) $operator->commission_rate;

        $balance = $this->availableBalance($operator->id, $rate);

        $history = Payout::where('operator_id', $operator->id)
            ->orderByDesc('requested_at')
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (int) $p->amount,
                'status' => $p->status,
                'note' => $p->note,
                'requested_at' => $p->requested_at->format('Y-m-d H:i'),
                'processed_at' => $p->processed_at?->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'success' => true,
            'data' => array_merge($balance, ['history' => $history]),
        ]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        $rate = (float) $operator->commission_rate;

        $balance = $this->availableBalance($operator->id, $rate);

        if ($balance['available'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không có số dư khả dụng để quyết toán',
            ], 422);
        }

        $payout = Payout::create([
            'operator_id' => $operator->id,
            'amount' => $balance['available'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu quyết toán '.number_format($payout->amount, 0, ',', '.').'đ. Admin sẽ xử lý trong 1–3 ngày làm việc.',
            'data' => ['amount' => (int) $payout->amount],
        ], 201);
    }
}
