<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payout;
use App\Models\Trip;
use App\Services\AdminNotificationService;
use App\Services\SettlementService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RevenueController extends Controller
{
    public function __construct(private readonly SettlementService $settlementService) {}

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Giải kỳ today/week/month/custom → [from, to, label] */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'month');

        // Kỳ custom: bắt buộc from_date/to_date hợp lệ (tránh Carbon::parse ném 500).
        if ($period === 'custom') {
            $request->validate([
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ]);
        }

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
    private function operatorTripIds(string $operatorId, $from = null, $to = null): Collection
    {
        return Trip::whereHas('vehicle', fn ($q) => $q->where('operator_id', $operatorId))
            ->when($from && $to, fn ($q) => $q->whereBetween('depart_at', [$from, $to]))
            ->pluck('id');
    }

    /** Query vé doanh thu thực nhận trên tập chuyến */
    private function realizedBookings($tripIds): Builder
    {
        return Booking::whereIn('trip_id', $tripIds)->completed()->paid();
    }

    // ── Endpoints ──────────────────────────────────────────────────────────

    public function summary(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to, $period] = $this->resolvePeriod($request);
        $rate = (float) $operator->commission_rate;

        // Cache-aside cho kỳ cố định (today/week/month) — chấp nhận cũ tối đa 60s. Kỳ custom
        // (báo cáo ad-hoc, khoảng ngày tuỳ ý) tính trực tiếp để không sinh vô số key.
        $data = $period === 'custom'
            ? $this->buildSummary($operator->id, $rate, $from, $to, $period)
            : Cache::remember(
                "operator:{$operator->id}:revenue:summary:{$period}",
                60,
                fn () => $this->buildSummary($operator->id, $rate, $from, $to, $period),
            );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Tính khối số liệu tổng quan doanh thu cho một kỳ (dùng chung cho cache-aside ở summary). */
    private function buildSummary(string $operatorId, float $rate, CarbonInterface $from, CarbonInterface $to, string $period): array
    {
        $tripIds = $this->operatorTripIds($operatorId, $from, $to);

        // Số tiền lấy từ SettlementService — NGUỒN TÍNH DUY NHẤT (đối soát online/cash).
        $s = $this->settlementService->forOperator($operatorId, $rate, $tripIds);
        $grossRevenue = $s['gross'];
        $commission = $s['commission'];
        $netRevenue = $grossRevenue - $commission; // doanh thu ròng = gross − hoa hồng (PRD F-O06)

        $completedTrips = Trip::whereIn('id', $tripIds)->where('status', 'completed')
            ->with('vehicle:id,seat_count')->get();

        // Vé thực nhận đều nằm trên chuyến đã hoàn thành → lấy MỘT lần, dùng cho cả số vé lẫn Σ khách.
        $realized = $this->realizedBookings($completedTrips->pluck('id'))->get(['id', 'passenger_count']);
        $totalPax = (int) $realized->sum('passenger_count');

        // Tỷ lệ lấp đầy THẬT = Σ khách / Σ ghế (cộng ghế THEO TỪNG CHUYẾN).
        $totalSeats = (int) $completedTrips->sum(fn ($t) => $t->vehicle->seat_count ?? 0);
        $occupancy = $totalSeats > 0 ? round($totalPax / $totalSeats * 100, 1) : 0;

        return [
            'period' => $period,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'total_trips' => $completedTrips->count(),
            'total_bookings' => $realized->count(),
            'total_passengers' => $totalPax,
            'gross_revenue' => $grossRevenue,
            'commission' => $commission,
            'commission_rate' => $rate,
            'net_revenue' => $netRevenue,
            // Tiền mặt nhà xe đã tự thu (ngoài nền tảng) vs số nền tảng sẽ chuyển/đối soát kỳ này.
            'cash_collected' => $s['cash_gross'],
            'settlement' => $s['settlement'],
            'avg_occupancy' => $occupancy,
        ];
    }

    public function daily(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        // Nhóm theo NGÀY CHẠY (trips.depart_at) bằng Collection — không dùng raw SQL.
        $daily = $this->realizedBookings($tripIds)
            ->with('trip:id,depart_at')
            ->get(['id', 'trip_id', 'final_amount'])
            ->groupBy(fn ($b) => $b->trip->depart_at->toDateString())
            ->map(fn ($g, $date) => [
                'date' => $date,
                'total_bookings' => $g->count(),
                'revenue' => (int) $g->sum('final_amount'),
            ])
            ->sortKeys()
            ->values();

        return response()->json(['success' => true, 'data' => $daily]);
    }

    /**
     * Chi tiết doanh thu theo chuyến để màn báo cáo hiển thị đúng tuyến, tài xế,
     * tổng khách trong ngày và số tiền quyết toán. Chỉ các vé đã hoàn thành + đã thanh
     * toán mới được tính, đồng nhất với summary/daily và SettlementService.
     */
    public function transactions(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $rate = (float) $operator->commission_rate;
        $perPage = min(50, max(5, (int) $request->integer('per_page', 10)));
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        $trips = Trip::whereIn('id', $tripIds)
            ->where('status', 'completed')
            ->whereHas('bookings', fn ($q) => $q->completed()->paid())
            ->with([
                'route:id,origin_city,dest_city',
                'driver.user:id,full_name',
                'vehicle:id,seat_count',
                'bookings' => fn ($q) => $q->completed()->paid()
                    ->select(['id', 'trip_id', 'passenger_count', 'final_amount']),
            ])
            ->orderByDesc('depart_at')
            ->paginate($perPage);

        // Tính trên toàn kỳ, không chỉ trang hiện tại, để tổng khách theo ngày vẫn
        // chính xác khi các chuyến cùng ngày nằm ở hai trang phân trang khác nhau.
        $dailyPassengers = $this->realizedBookings($tripIds)
            ->with('trip:id,depart_at')
            ->get(['id', 'trip_id', 'passenger_count'])
            ->groupBy(fn ($booking) => $booking->trip->depart_at->toDateString())
            ->map(fn ($bookings) => (int) $bookings->sum('passenger_count'));

        $rows = $trips->getCollection()->map(function (Trip $trip) use ($rate, $dailyPassengers) {
            $gross = (int) $trip->bookings->sum('final_amount');
            $commission = (int) round($gross * $rate / 100);
            $date = $trip->depart_at->toDateString();

            return [
                'id' => $trip->id,
                'date' => $date,
                'route' => "{$trip->route->origin_city} → {$trip->route->dest_city}",
                'driver' => $trip->driver?->user?->full_name ?? 'Chưa phân công',
                'passengers' => (int) $trip->bookings->sum('passenger_count'),
                'daily_passengers' => (int) $dailyPassengers->get($date, 0),
                'seat_count' => (int) ($trip->vehicle?->seat_count ?? 0),
                'gross_revenue' => $gross,
                'commission' => $commission,
                'net_revenue' => $gross - $commission,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'current_page' => $trips->currentPage(),
                'last_page' => $trips->lastPage(),
                'per_page' => $trips->perPage(),
                'total' => $trips->total(),
            ],
        ]);
    }

    public function byRoute(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        // Gộp theo tuyến bằng Collection — không dùng raw SQL.
        $rows = $this->realizedBookings($tripIds)
            ->with('trip.route')
            ->get(['id', 'trip_id', 'final_amount'])
            ->groupBy(fn ($b) => $b->trip->route_id)
            ->map(function ($g) {
                $route = $g->first()->trip->route;

                return [
                    'name' => "{$route->origin_city} → {$route->dest_city}",
                    'total_bookings' => $g->count(),
                    'revenue' => (int) $g->sum('final_amount'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function byDriver(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;
        [$from, $to] = $this->resolvePeriod($request);
        $tripIds = $this->operatorTripIds($operator->id, $from, $to);

        // Gộp theo tài xế bằng Collection — không dùng raw SQL.
        $rows = $this->realizedBookings($tripIds)
            ->with('trip.driver.user')
            ->get(['id', 'trip_id', 'final_amount'])
            ->groupBy(fn ($b) => $b->trip->driver_id)
            ->map(fn ($g) => [
                'name' => $g->first()->trip->driver->user->full_name,
                'total_bookings' => $g->count(),
                'revenue' => (int) $g->sum('final_amount'),
            ])
            ->sortByDesc('revenue')
            ->values();

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

        // Khóa nhà xe + tính available TRONG transaction để 2 yêu cầu đồng thời không
        // cùng vượt qua check rồi tạo 2 payout vượt settlement (R2).
        $payout = DB::transaction(function () use ($operator, $rate) {
            DB::table('operators')->where('id', $operator->id)->lockForUpdate()->first();

            $available = $this->availableBalance($operator->id, $rate)['available'];
            if ($available <= 0) {
                return null;
            }

            return Payout::create([
                'operator_id' => $operator->id,
                'amount' => $available,
                'status' => 'pending',
                'requested_at' => now(),
            ]);
        });

        if (! $payout) {
            return response()->json([
                'success' => false,
                'message' => 'Không có số dư khả dụng để quyết toán',
            ], 422);
        }

        app(AdminNotificationService::class)->notify(
            'finance.payout',
            'Yêu cầu quyết toán mới',
            "Nhà xe \"{$operator->company_name}\" yêu cầu quyết toán ".number_format($payout->amount, 0, ',', '.').'đ.',
            ['kind' => 'payout_request', 'link' => '/admin/finance', 'operator_id' => $operator->id, 'amount' => (int) $payout->amount],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi yêu cầu quyết toán '.number_format($payout->amount, 0, ',', '.').'đ. Admin sẽ xử lý trong 1–3 ngày làm việc.',
            'data' => ['amount' => (int) $payout->amount],
        ], 201);
    }
}
