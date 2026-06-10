<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingRepository implements BookingRepositoryInterface
{
    public function findById(string $id): ?Booking
    {
        return Booking::with([
            'trip.route',
            'trip.driver.user:id,full_name,phone',
            'trip.vehicle:id,plate_number,vehicle_type',
            'pickupStop',
            'dropoffStop',
            'passengers',
        ])->find($id);
    }

    public function findByCode(string $code): ?Booking
    {
        return Booking::with([
            'trip.route',
            'trip.driver.user:id,full_name',
            'passengers',
            'pickupStop',
        ])->where('booking_code', $code)->first();
    }

    public function findByQrToken(string $token): ?Booking
    {
        return Booking::with(['trip', 'passengers'])
                      ->where('qr_token', $token)
                      ->first();
    }

    public function findByUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::with(['trip.route', 'pickupStop', 'dropoffStop'])
                        ->where('user_id', $userId)
                        ->latest();

        // Map nhóm tab của FE (upcoming/past/cancelled) → trạng thái thật.
        // QUAN TRỌNG: "Sắp đi" phải xét cả CHUYẾN (depart_at/status), không chỉ booking_status —
        // tránh vé confirmed nằm trên chuyến đã chạy/đã đóng vẫn hiện ở "Sắp đi".
        $active = ['pending', 'confirmed', 'checked_in'];

        if (!empty($filters['status'])) {
            match ($filters['status']) {
                // Sắp đi: vé còn hiệu lực VÀ chuyến chưa khởi hành & chưa đóng
                'upcoming'  => $query->whereIn('booking_status', $active)
                    ->whereHas('trip', fn ($t) => $t
                        ->where('depart_at', '>=', now())
                        ->whereNotIn('status', ['completed', 'cancelled'])),

                // Đã đi: vé đã chốt (completed/no_show) HOẶC vé còn hiệu lực nhưng chuyến đã qua giờ/đã đóng (vé "mồ côi")
                'past'      => $query->where(function ($q) use ($active) {
                    $q->whereIn('booking_status', ['completed', 'no_show'])
                      ->orWhere(fn ($q2) => $q2
                          ->whereIn('booking_status', $active)
                          ->whereHas('trip', fn ($t) => $t
                              ->where('depart_at', '<', now())
                              ->orWhereIn('status', ['completed', 'cancelled'])));
                }),

                'cancelled' => $query->where('booking_status', 'cancelled'),
                default     => $query->where('booking_status', $filters['status']), // fallback enum thật
            };
        }

        return $query->paginate(10);
    }

    public function findByTrip(string $tripId, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::with(['user:id,full_name,phone', 'passengers', 'pickupStop'])
                        ->where('trip_id', $tripId);

        if (!empty($filters['status'])) {
            $query->where('booking_status', $filters['status']);
        }

        return $query->paginate(20);
    }

    public function findByOperator(string $operatorId, array $filters = []): LengthAwarePaginator
    {
        $query = Booking::with(['user:id,full_name,phone', 'passengers.seatMap', 'pickupStop', 'dropoffStop', 'trip.route'])
                        ->whereHas('trip', function ($q) use ($operatorId) {
                            $q->whereHas('vehicle', fn($vq) => $vq->where('operator_id', $operatorId));
                        });

        if (!empty($filters['status'])) {
            // FE gửi nhóm trạng thái (pending_payment/confirmed/...) → map sang enum thật
            $statusMap = [
                'pending_payment' => ['pending'],
                'confirmed'       => ['confirmed', 'checked_in'],
                'completed'       => ['completed'],
                'cancelled'       => ['cancelled', 'no_show'],
            ];
            $query->whereIn('booking_status', $statusMap[$filters['status']] ?? [$filters['status']]);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('booking_code', 'like', "%{$filters['search']}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('phone', 'like', "%{$filters['search']}%"));
            });
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function findAllForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = Booking::with([
            'user:id,full_name,phone',
            'trip:id,depart_at,route_id',
            'trip.route:id,origin_city,dest_city',
        ]);

        if (!empty($filters['status'])) {
            $query->where('booking_status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('booking_code', 'like', "%{$term}%")
                  ->orWhere('contact_name', 'like', "%{$term}%")
                  ->orWhere('contact_phone', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return Booking::where('id', $id)->update($data) > 0;
    }

    public function updateStatus(string $id, string $status): bool
    {
        return Booking::where('id', $id)->update(['booking_status' => $status]) > 0;
    }

    public function countPendingByUser(string $userId): int
    {
        return Booking::where('user_id', $userId)
                      ->where('booking_status', 'pending')
                      ->count();
    }
}
