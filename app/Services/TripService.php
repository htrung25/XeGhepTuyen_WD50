<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DriverStatus;
use App\Enums\TripStatus;
use App\Events\TripCompleted;
use App\Events\TripStarted;
use App\Exceptions\TripNotAvailableException;
use App\Models\Driver;
use App\Models\Route;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Repositories\Contracts\TripRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripService
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepo,
        private readonly BookingService $bookingService,
    ) {}

    public function search(array $filters): array
    {
        // NOTE: không cache Eloquent model (cache store = database sẽ serialize model
        // và unserialize lỗi "incomplete object"). Cache danh sách ID thay thế.
        $cacheKey = 'trips:search:'.md5(serialize($filters));

        $ids = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($filters) {
            return $this->tripRepo->search($filters)->pluck('id')->all();
        });

        if (empty($ids)) {
            return [];
        }

        return $this->tripRepo->findManyForSearch($ids);
    }

    public function create(array $data): Trip
    {
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $driver = Driver::findOrFail($data['driver_id']);

        // Xe & tài xế phải thuộc đúng nhà xe tạo chuyến
        if (! empty($data['operator_id'])) {
            if ($vehicle->operator_id !== $data['operator_id']) {
                throw new \InvalidArgumentException('Xe không thuộc nhà xe của bạn');
            }
            if ($driver->operator_id !== $data['operator_id']) {
                throw new \InvalidArgumentException('Tài xế không thuộc nhà xe của bạn');
            }
        }

        // Chỉ tài xế đã được admin duyệt GPLX (verified) mới được xếp vào chuyến.
        if ($driver->status !== DriverStatus::Verified) {
            throw new \InvalidArgumentException('Tài xế chưa được admin duyệt, không thể xếp vào chuyến');
        }

        if (empty($data['arrive_at'])) {
            $route = Route::findOrFail($data['route_id']);
            $data['arrive_at'] = Carbon::parse($data['depart_at'])
                ->addMinutes($route->est_duration_min);
        }

        $departAt = Carbon::parse($data['depart_at']);
        $arriveAt = Carbon::parse($data['arrive_at']);

        // Giờ xuất phát phải cách hiện tại tối thiểu min_lead_minutes — đồng bộ với
        // luật đặt vé của khách (Trip::scopeAvailable/canBeBooked), tránh tạo chuyến
        // không bao giờ bán được.
        $minLead = (int) config('booking.min_lead_minutes', 30);
        if ($departAt->lt(now()->addMinutes($minLead))) {
            throw new \InvalidArgumentException("Giờ xuất phát phải cách hiện tại ít nhất {$minLead} phút");
        }

        // Chặn trùng lịch: xe HOẶC tài xế đã có chuyến (chưa hủy) chồng khung giờ
        $overlap = Trip::whereIn('status', [TripStatus::Scheduled, TripStatus::Boarding, TripStatus::InProgress])
            ->where(fn ($q) => $q->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $driver->id))
            ->where('depart_at', '<', $arriveAt)
            ->where('arrive_at', '>', $departAt)
            ->exists();
        if ($overlap) {
            throw new \InvalidArgumentException('Xe hoặc tài xế đã có chuyến trùng khung giờ này');
        }

        $trip = Trip::create(array_merge($data, [
            'available_seats' => $vehicle->seat_count,
            'tracking_code' => strtoupper(Str::random(8)),
            'status' => $data['status'] ?? TripStatus::Scheduled,
        ]));

        $this->generateSeatMap($trip, $vehicle);

        return $trip;
    }

    /**
     * Tạo hàng loạt chuyến (tạo lịch cả tuần).
     * FE đã bung sẵn từng chuyến: [{route_id, vehicle_id, driver_id, depart_at, base_price}, ...].
     * Best-effort: bỏ qua chuyến trùng giờ / không hợp lệ, tạo các chuyến còn lại.
     *
     * @return array{created:int, skipped:int}
     */
    public function bulkCreate(array $trips, string $operatorId): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($trips as $t) {
            try {
                $this->create([
                    'route_id' => $t['route_id'],
                    'vehicle_id' => $t['vehicle_id'],
                    'driver_id' => $t['driver_id'],
                    'depart_at' => $t['depart_at'],
                    'price' => $t['base_price'] ?? $t['price'] ?? null,
                    'operator_id' => $operatorId,
                ]);
                $created++;
            } catch (\InvalidArgumentException|TripNotAvailableException $e) {
                // Chuyến trùng khung giờ / xe-tài xế không thuộc nhà xe → bỏ qua, tạo tiếp
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function startTrip(Trip $trip): void
    {
        $trip->update([
            'status' => TripStatus::InProgress,
            'started_at' => now(),
        ]);

        event(new TripStarted($trip));
    }

    public function completeTrip(Trip $trip): void
    {
        DB::transaction(function () use ($trip) {
            $trip->update([
                'status' => TripStatus::Completed,
                'completed_at' => now(),
            ]);

            // Tất toán vé: checked_in→completed, confirmed→no_show, pending→cancelled
            $this->bookingService->finalizeOnTripComplete($trip);
        });

        event(new TripCompleted($trip));
    }

    /**
     * Nhà xe xác nhận chuyến QUÁ GIỜ thực tế ĐÃ CHẠY XONG (tài xế quên cập nhật app).
     *
     * Khác completeTrip(): vé 'confirmed' (chưa quét QR check-in) được coi là ĐÃ ĐI
     * → completed (ghi nhận doanh thu), KHÔNG đánh no_show / KHÔNG hoàn tiền oan.
     * Vé 'pending' (chưa thanh toán) → hủy, giải phóng ghế.
     */
    public function markRanCompleted(Trip $trip): void
    {
        if (in_array($trip->status, [TripStatus::Completed, TripStatus::Cancelled], true)) {
            return;
        }

        DB::transaction(function () use ($trip) {
            $trip->update([
                'status' => TripStatus::Completed,
                'started_at' => $trip->started_at ?? $trip->depart_at,
                'completed_at' => now(),
            ]);

            // confirmed + checked_in → completed (đã đi, ghi nhận doanh thu)
            $trip->bookings()
                ->whereIn('booking_status', [BookingStatus::Confirmed->value, BookingStatus::CheckedIn->value])
                ->update(['booking_status' => BookingStatus::Completed, 'completed_at' => now()]);

            // pending (chưa thanh toán) → hủy
            $trip->bookings()
                ->where('booking_status', BookingStatus::Pending->value)
                ->update([
                    'booking_status' => BookingStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => 'Chuyến đã kết thúc, vé chưa thanh toán',
                ]);
        });

        event(new TripCompleted($trip));
    }

    /**
     * Hủy chuyến (do nhà xe/admin hoặc tự động khi quá giờ không thực hiện).
     * Hoàn 100% + bồi thường cho mọi vé còn hiệu lực.
     */
    public function cancelTrip(Trip $trip, string $reason, bool $compensate = true): void
    {
        if (in_array($trip->status, [TripStatus::Completed, TripStatus::Cancelled], true)) {
            return;
        }

        DB::transaction(function () use ($trip, $reason, $compensate) {
            $trip->update([
                'status' => TripStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $bookings = $trip->bookings()
                ->whereIn('booking_status', [
                    BookingStatus::Pending->value,
                    BookingStatus::Confirmed->value,
                    BookingStatus::CheckedIn->value,
                ])
                ->with('user')
                ->get();

            foreach ($bookings as $booking) {
                $this->bookingService->cancelByOperator($booking, $reason, $compensate);
            }
        });
    }

    private function generateSeatMap(Trip $trip, Vehicle $vehicle): void
    {
        $templates = $this->getSeatTemplate($vehicle);

        foreach ($templates as $seatCode) {
            SeatMap::create([
                'trip_id' => $trip->id,
                'seat_code' => $seatCode,
                'seat_type' => 'standard',
                'price' => $trip->price,
                'status' => 'available',
            ]);
        }
    }

    private function getSeatTemplate(Vehicle $vehicle): array
    {
        return match ($vehicle->vehicle_type->value) {
            'mpv_7' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1'],
            'van_9' => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1', 'D2', 'E1'],
            'minibus_16' => array_merge(
                ...array_map(fn ($r) => ["{$r}1", "{$r}2", "{$r}3", "{$r}4"], ['A', 'B', 'C', 'D'])
            ),
            default => ['A1', 'A2', 'B1', 'B2'],
        };
    }
}
