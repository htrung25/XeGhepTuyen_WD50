<?php

namespace App\Http\Controllers\Driver;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Driver\TripResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripRepositoryInterface $tripRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        // Chỉ lọc theo ngày khi client gửi 'date' rõ ràng. Nếu không gửi (Schedule
        // tuần cần toàn bộ chuyến được phân công) thì trả tất cả, không giới hạn hôm nay.
        $resolvedDate = match (true) {
            blank($request->date)         => null,
            $request->date === 'today'    => today()->toDateString(),
            default                       => $request->date,
        };

        $trips = $this->tripRepo->findByDriver($driver->id, [
            'date'   => $resolvedDate,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'data'    => TripResource::collection($trips),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $trip   = $this->tripRepo->findById($id);
        $driver = auth('driver')->user()->driver;

        if (!$trip || $trip->driver_id !== $driver->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new TripResource($trip)]);
    }

    public function passengers(string $id): JsonResponse
    {
        $trip   = $this->tripRepo->findById($id);
        $driver = auth('driver')->user()->driver;

        if (!$trip || $trip->driver_id !== $driver->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        $passengers = $trip->bookings()
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->with(['passengers.seatMap', 'pickupStop', 'dropoffStop'])
            ->get()
            ->flatMap(function ($booking) {
                return $booking->passengers->map(fn($p) => [
                    'id'              => $p->id,
                    'booking_id'      => $booking->id,
                    'passenger_name'  => $p->full_name,
                    'passenger_phone' => $p->phone,
                    'seat_codes'      => [$p->seatMap?->seat_code],
                    'pickup_stop'     => [
                        'stop_name' => $booking->pickupStop?->stop_name,
                        'address'   => $booking->pickupStop?->address,
                    ],
                    'dropoff_stop'    => [
                        'stop_name' => $booking->dropoffStop?->stop_name,
                        'address'   => $booking->dropoffStop?->address,
                    ],
                    'booking_status'  => $booking->booking_status->value,
                    'checked_in'      => $booking->booking_status->value === 'checked_in',
                    'payment_method'  => $booking->payment_method?->value,
                    'payment_status'  => $booking->payment_status->value,
                    // Tiền tài xế cần thu (chỉ tiền mặt & chưa thanh toán)
                    'amount_due'      => ($booking->payment_method?->value === 'cash'
                        && $booking->payment_status->value === 'unpaid')
                        ? (int) $booking->final_amount : 0,
                ]);
            });

        return response()->json([
            'success' => true,
            'data'    => $passengers->values(),
        ]);
    }

    public function start(string $id): JsonResponse
    {
        $trip   = $this->tripRepo->findById($id);
        $driver = auth('driver')->user()->driver;

        if (!$trip || $trip->driver_id !== $driver->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        if ($trip->status !== TripStatus::Boarding && $trip->status !== TripStatus::Scheduled) {
            return response()->json(['success' => false, 'message' => 'Không thể bắt đầu chuyến này'], 422);
        }

        try {
            $this->tripService->startTrip($trip);
            return response()->json(['success' => true, 'message' => 'Chuyến đã bắt đầu']);
        } catch (\Exception $e) {
            Log::error('Trip start failed', ['error' => $e->getMessage(), 'trip' => $id]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function complete(string $id): JsonResponse
    {
        $trip   = $this->tripRepo->findById($id);
        $driver = auth('driver')->user()->driver;

        if (!$trip || $trip->driver_id !== $driver->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        if ($trip->status !== TripStatus::InProgress) {
            return response()->json(['success' => false, 'message' => 'Chuyến chưa được bắt đầu'], 422);
        }

        try {
            $this->tripService->completeTrip($trip);
            return response()->json(['success' => true, 'message' => 'Chuyến đã hoàn thành']);
        } catch (\Exception $e) {
            Log::error('Trip complete failed', ['error' => $e->getMessage(), 'trip' => $id]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $driver = auth('driver')->user()->driver;

        $trips = $this->tripRepo->findByDriver($driver->id, ['status' => 'completed']);

        return response()->json(['success' => true, 'data' => TripResource::collection($trips)]);
    }
}
