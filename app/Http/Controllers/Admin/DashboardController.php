<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LiveTripResource;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\PartnerApplication;
use App\Models\Payout;
use App\Models\Trip;
use App\Models\User;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepo,
    ) {}

    public function index(): JsonResponse
    {
        $stats = Cache::remember('admin_dashboard_stats', 60, function () {
            return [
                'users' => User::count(),
                'operators' => Operator::where('status', 'verified')->count(),
                'drivers' => Driver::where('status', 'verified')->count(),
                'trips_today' => Trip::whereDate('depart_at', today())->count(),
                'trips_in_progress' => Trip::where('status', 'in_progress')->count(),
                'bookings_today' => Booking::whereDate('created_at', today())->count(),
                'revenue_today' => Booking::whereDate('created_at', today())->where('booking_status', 'completed')->sum('final_amount'),
                'pending_operators' => Operator::where('status', 'pending')->count(),
                'pending_drivers' => Driver::where('status', 'pending')->count(),
            ];
        });

        // Booking gần đây — map đúng shape FE cần (code/customer/route/amount/status)
        $recentBookings = Booking::with(['user', 'trip.route'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Booking $b) => [
                'code' => $b->booking_code,
                'customer' => $b->contact_name ?? $b->user?->full_name,
                'route' => $b->trip?->route
                    ? $b->trip->route->origin_city.' → '.$b->trip->route->dest_city
                    : '—',
                'amount' => (int) $b->final_amount,
                'status' => $b->booking_status->value,
                'created_at' => $b->created_at->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'data' => ['stats' => $stats, 'recent_bookings' => $recentBookings],
        ]);
    }

    /**
     * Vị trí GPS các chuyến đang chạy cho widget bản đồ ở dashboard.
     * Dùng chung nguồn với /admin/trips/monitor để 2 màn hình khớp nhau.
     */
    public function map(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => LiveTripResource::collection($this->tripRepo->findInProgress()),
        ]);
    }

    /**
     * Số việc đang CHỜ XỬ LÝ theo từng mục sidebar (badge). Phản ánh trạng thái
     * thực tế (không phụ thuộc thông báo), gate theo quyền của admin đang đăng nhập.
     */
    public function pendingCounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $counts = [];

        if ($user->hasPermission('operators.view')) {
            $counts['/admin/operators'] = PartnerApplication::where('status', 'pending')->count();
        }
        if ($user->hasPermission('drivers.view')) {
            $counts['/admin/drivers'] = Driver::where('status', 'pending')->count();
        }
        if ($user->hasPermission('finance.view')) {
            $counts['/admin/finance'] = Payout::where('status', 'pending')->count();
        }

        return response()->json(['success' => true, 'data' => $counts]);
    }
}
