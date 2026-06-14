<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $stats = Cache::remember('admin_dashboard_stats', 60, function () {
            return [
                'users'           => User::count(),
                'operators'       => Operator::where('status', 'verified')->count(),
                'drivers'         => Driver::where('status', 'verified')->count(),
                'trips_today'     => Trip::whereDate('depart_at', today())->count(),
                'trips_in_progress' => Trip::where('status', 'in_progress')->count(),
                'bookings_today'  => Booking::whereDate('created_at', today())->count(),
                'revenue_today'   => Booking::whereDate('created_at', today())->where('booking_status', 'completed')->sum('final_amount'),
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
                                     'code'       => $b->booking_code,
                                     'customer'   => $b->contact_name ?? $b->user?->full_name,
                                     'route'      => $b->trip?->route
                                         ? $b->trip->route->origin_city . ' → ' . $b->trip->route->dest_city
                                         : '—',
                                     'amount'     => (int) $b->final_amount,
                                     'status'     => $b->booking_status->value,
                                     'created_at' => $b->created_at->format('Y-m-d H:i:s'),
                                 ]);

        return response()->json([
            'success' => true,
            'data'    => ['stats' => $stats, 'recent_bookings' => $recentBookings],
        ]);
    }
}
