<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly TripRepositoryInterface $tripRepo,
        private readonly TrackingService $trackingService,
    ) {}

    public function trackByBooking(string $bookingId): JsonResponse
    {
        $user    = auth('customer')->user();
        $booking = $this->bookingRepo->findById($bookingId);

        if (!$booking || $booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Vé không tồn tại'], 404);
        }

        $trip     = $booking->trip;
        $location = $this->trackingService->getDriverLocation($trip->driver);

        return response()->json([
            'success' => true,
            'data'    => [
                'trip_id'       => $trip->id,
                'trip_status'   => $trip->status->value,
                'driver'        => ['full_name' => $trip->driver?->user?->full_name, 'phone' => $trip->driver?->user?->phone],
                'vehicle'       => ['plate' => $trip->vehicle->plate_number, 'type' => $trip->vehicle->vehicle_type->value],
                'driver_location' => $location,
            ],
        ]);
    }

    public function trackByCode(string $trackingCode): JsonResponse
    {
        $trip = $this->tripRepo->findByTrackingCode($trackingCode);

        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Mã theo dõi không hợp lệ'], 404);
        }

        $location = $this->trackingService->getDriverLocation($trip->driver);

        return response()->json([
            'success' => true,
            'data'    => [
                'trip_id'         => $trip->id,
                'trip_status'     => $trip->status->value,
                'depart_at'       => $trip->depart_at->format('Y-m-d H:i:s'),
                'arrive_at'       => $trip->arrive_at?->format('Y-m-d H:i:s'),
                'route'           => "{$trip->route->origin_city} → {$trip->route->dest_city}",
                'driver_location' => $location,
            ],
        ]);
    }
}
