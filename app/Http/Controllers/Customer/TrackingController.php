<?php

namespace App\Http\Controllers\Customer;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Trip;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepo,
        private readonly TripRepositoryInterface $tripRepo,
        private readonly TrackingService $trackingService,
    ) {}

    public function trackByBooking(string $bookingId): JsonResponse
    {
        $user = auth('customer')->user();
        $booking = $this->bookingRepo->findById($bookingId);

        if (! $booking || $booking->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Vé không tồn tại'], 404);
        }

        $trip = $booking->trip->loadMissing(['route.stops', 'driver.user', 'vehicle']);
        $driver = $trip->driver;
        $location = $driver ? $this->trackingService->getLocation($driver) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'trip_status' => $trip->status->value,
                'booking_code' => $booking->booking_code,
                'driver' => [
                    'full_name' => $driver?->user?->full_name,
                    'phone' => $driver?->user?->phone,
                    'rating_avg' => $driver ? (float) $driver->rating_avg : null,
                ],
                'vehicle' => [
                    'plate_number' => $trip->vehicle->plate_number,
                    'brand' => $trip->vehicle->brand,
                    'model' => $trip->vehicle->model,
                    'type' => $trip->vehicle->vehicle_type->value,
                ],
                'driver_lat' => $location['lat'] ?? null,
                'driver_lng' => $location['lng'] ?? null,
                'stops' => $this->buildStopTimeline($trip, $booking),
            ],
        ]);
    }

    /**
     * Timeline điểm dừng cho trang theo dõi: giờ dự kiến = depart_at + offset_minutes,
     * trạng thái (done/current/upcoming) suy từ trạng thái chuyến và giờ hiện tại.
     */
    private function buildStopTimeline(Trip $trip, Booking $booking): array
    {
        $now = now();
        $currentAssigned = false;

        $items = $trip->route->stops->values()->map(function ($stop, int $i) use ($trip, $booking, $now, &$currentAssigned) {
            $time = $trip->depart_at->addMinutes((int) ($stop->offset_minutes ?? 0));

            $status = match ($trip->status) {
                TripStatus::Completed => 'done',
                TripStatus::Boarding => $i === 0 ? 'current' : 'upcoming',
                TripStatus::InProgress => $time->lte($now) ? 'done' : ($currentAssigned ? 'upcoming' : 'current'),
                default => 'upcoming',
            };
            if ($status === 'current') {
                $currentAssigned = true;
            }

            return [
                'id' => $stop->id,
                'name' => $stop->stop_name,
                'time' => $time->format('H:i'),
                'status' => $status,
                'is_your_pickup' => $stop->id === $booking->pickup_stop_id,
                'is_your_dropoff' => $stop->id === $booking->dropoff_stop_id,
            ];
        })->all();

        // Xe đang chạy nhưng đã quá mọi mốc giờ → giữ điểm cuối là "đang đến" thay vì đã xong hết.
        if ($trip->status === TripStatus::InProgress && ! $currentAssigned && $items !== []) {
            $items[array_key_last($items)]['status'] = 'current';
        }

        return $items;
    }

    public function trackByCode(string $trackingCode): JsonResponse
    {
        $trip = $this->tripRepo->findByTrackingCode($trackingCode);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Mã theo dõi không hợp lệ'], 404);
        }

        $location = $trip->driver ? $this->trackingService->getLocation($trip->driver) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'trip_status' => $trip->status->value,
                'depart_at' => $trip->depart_at->format('Y-m-d H:i:s'),
                'arrive_at' => $trip->arrive_at?->format('Y-m-d H:i:s'),
                'route' => "{$trip->route->origin_city} → {$trip->route->dest_city}",
                'driver_location' => $location,
            ],
        ]);
    }
}
