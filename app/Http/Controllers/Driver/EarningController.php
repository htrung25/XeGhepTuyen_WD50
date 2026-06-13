<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningController extends Controller
{
    /** Vé tính là thu nhập THỰC NHẬN của tài xế */
    private const REALIZED = ['booking_status' => 'completed'];

    /**
     * Bảng kê thu nhập tài xế (CHỈ XEM).
     * Nền tảng quyết toán cho nhà xe; nhà xe trả tài xế trực tiếp — nền tảng
     * KHÔNG giữ tiền của tài xế nên đây chỉ là số liệu tham chiếu.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        $period = $request->get('period', 'week');

        [$from, $to] = match ($period) {
            'today' => [today(), today()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };

        // ── Thống kê theo kỳ (chuyến đã hoàn thành trong kỳ) ──────────────────
        $periodTrips = $driver->trips()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->with('route:id,distance_km')
            ->get();

        $periodTripIds = $periodTrips->pluck('id');
        $periodBookings = $this->realizedBookings($periodTripIds);
        $periodRevenue = (int) $periodBookings->sum('final_amount');
        $periodPax = (int) $periodBookings->sum('passenger_count');
        $totalKm = (int) $periodTrips->sum(fn ($t) => $t->route->distance_km ?? 0);

        // ── Tổng tích lũy (toàn thời gian) ────────────────────────────────────
        $allTripIds = $driver->trips()->where('status', 'completed')->pluck('id');
        $totalEarned = (int) $this->realizedBookings($allTripIds)->sum('final_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_earned' => $totalEarned,
                'trip_count' => $periodTrips->count(),
                'passenger_count' => $periodPax,
                'total_km' => $totalKm,
                'total' => $periodRevenue,
                'daily_amounts' => $this->dailyAmounts($driver),
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        $trips = $driver->trips()
            ->where('status', 'completed')
            ->with('route:id,origin_city,dest_city')
            ->withCount(['bookings as passenger_count' => fn ($q) => $q->where('booking_status', 'completed')])
            ->withSum(['bookings as amount' => fn ($q) => $q->where('booking_status', 'completed')], 'final_amount')
            ->orderByDesc('completed_at')
            ->paginate(10);

        $data = collect($trips->items())->map(fn ($t) => [
            'id' => $t->id,
            'route' => $t->route ? "{$t->route->origin_city} → {$t->route->dest_city}" : 'Chuyến đi',
            'date' => optional($t->completed_at)->toIso8601String(),
            'passenger_count' => (int) $t->passenger_count,
            'amount' => (int) ($t->amount ?? 0),
            'status' => 'paid',
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => ['current_page' => $trips->currentPage(), 'total' => $trips->total()],
        ]);
    }

    /** Query vé thực nhận trên tập chuyến */
    private function realizedBookings($tripIds)
    {
        return Booking::whereIn('trip_id', $tripIds)
            ->where('booking_status', self::REALIZED['booking_status']);
    }

    /** Doanh thu 7 ngày gần nhất (index 0 = 6 ngày trước … index 6 = hôm nay) */
    private function dailyAmounts($driver): array
    {
        $tripIds = $driver->trips()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [today()->subDays(6), now()])
            ->pluck('id');

        $byDay = Booking::whereIn('trip_id', $tripIds)
            ->where('booking_status', self::REALIZED['booking_status'])
            ->join('trips', 'bookings.trip_id', '=', 'trips.id')
            ->selectRaw('DATE(trips.completed_at) as d, SUM(bookings.final_amount) as amt')
            ->groupByRaw('DATE(trips.completed_at)')
            ->pluck('amt', 'd');

        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i)->toDateString();
            $daily[] = (int) ($byDay[$day] ?? 0);
        }

        return $daily;
    }
}
