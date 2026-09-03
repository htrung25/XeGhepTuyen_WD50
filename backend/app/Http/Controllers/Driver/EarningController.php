<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningController extends Controller
{
    /**
     * Bảng kê thu nhập tài xế (CHỈ XEM).
     * Nền tảng quyết toán cho nhà xe; nhà xe trả tài xế trực tiếp — nền tảng
     * KHÔNG giữ tiền của tài xế nên đây chỉ là số liệu tham chiếu.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        $period = $request->get('period', 'week');
        [$from, $to] = $this->periodRange($period);

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

        // Lịch sử thu nhập phải theo ĐÚNG kỳ đang chọn ở bộ lọc — trước đây
        // endpoint bỏ qua period nên đổi Hôm nay/Tuần này/Tháng này thì danh
        // sách vẫn y nguyên (luôn là toàn bộ chuyến đã chạy).
        [$from, $to] = $this->periodRange($request->get('period', 'week'));

        $trips = $driver->trips()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $to])
            ->with('route:id,origin_city,origin_district,dest_city,dest_district')
            ->withCount(['bookings as passenger_count' => fn ($q) => $q->where('booking_status', 'completed')])
            ->withSum(['bookings as amount' => fn ($q) => $q->where('booking_status', 'completed')], 'final_amount')
            ->orderByDesc('completed_at')
            ->paginate(10);

        $data = collect($trips->items())->map(fn ($t) => [
            'id' => $t->id,
            'route' => $t->route
                ? collect([$t->route->origin_district, $t->route->origin_city])->filter()->implode(', ')
                    .' → '.collect([$t->route->dest_district, $t->route->dest_city])->filter()->implode(', ')
                : 'Chuyến đi',
            'date' => optional($t->completed_at)->toIso8601String(),
            'passenger_count' => (int) $t->passenger_count,
            'amount' => (int) ($t->amount ?? 0),
            'status' => 'paid',
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $trips->currentPage(),
                'per_page' => $trips->perPage(),
                'total' => $trips->total(),
                // Thiếu last_page nên client phải đoán "còn trang sau" bằng cách
                // xem trang hiện tại có đầy 10 mục không — đoán sai ở trang cuối.
                'last_page' => $trips->lastPage(),
            ],
        ]);
    }

    /** Khoảng thời gian của kỳ lọc — dùng chung cho cả tổng quan lẫn lịch sử */
    private function periodRange(string $period): array
    {
        return match ($period) {
            'today' => [today(), today()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };
    }

    /** Query vé thực nhận trên tập chuyến */
    private function realizedBookings($tripIds): Builder
    {
        return Booking::whereIn('trip_id', $tripIds)->completed();
    }

    /** Doanh thu 7 ngày gần nhất (index 0 = 6 ngày trước … index 6 = hôm nay) */
    private function dailyAmounts($driver): array
    {
        $tripIds = $driver->trips()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [today()->subDays(6), now()])
            ->pluck('id');

        $byDay = Booking::whereIn('trip_id', $tripIds)
            ->completed()
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
