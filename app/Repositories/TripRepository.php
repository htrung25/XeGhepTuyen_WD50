<?php

namespace App\Repositories;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TripRepository implements TripRepositoryInterface
{
    public function findById(string $id): ?Trip
    {
        return Trip::with([
            'route.stops',
            'route.operator:id,company_name',
            'vehicle.operator:id,company_name',
            'driver.user:id,full_name,phone',
            'seatMaps',
        ])->withCount('bookings')->find($id);
    }

    public function findByTrackingCode(string $code): ?Trip
    {
        return Trip::with(['route', 'driver.user:id,full_name,phone', 'vehicle:id,plate_number'])
                   ->where('tracking_code', $code)
                   ->first();
    }

    public function search(array $filters): Collection
    {
        $query = Trip::with([
            'route',
            'vehicle:id,plate_number,vehicle_type,amenities,seat_count',
            'driver:id,user_id,rating_avg',
            'driver.user:id,full_name,avatar_url',
        ])
        ->available($filters['passengers'] ?? 1);

        if (!empty($filters['from_city'])) {
            $query->whereHas('route', fn($q) => $q->where('origin_city', $filters['from_city']));
        }

        if (!empty($filters['to_city'])) {
            $query->whereHas('route', fn($q) => $q->where('dest_city', $filters['to_city']));
        }

        if (!empty($filters['date'])) {
            $query->whereDate('depart_at', $filters['date']);
        }

        $sort = $filters['sort'] ?? 'depart_asc';
        match($sort) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default      => $query->orderBy('depart_at'),
        };

        return $query->get();
    }

    public function findManyForSearch(array $ids): array
    {
        return Trip::with([
            'route.stops',
            'route.operator:id,company_name',
            'vehicle:id,plate_number,vehicle_type,amenities,seat_count',
            'driver:id,user_id,rating_avg,total_trips',
            'driver.user:id,full_name,avatar_url',
            'seatMaps',
        ])
        ->whereIn('id', $ids)
        ->get()
        ->sortBy(fn($trip) => array_search($trip->id, $ids, true))
        ->values()
        ->all();
    }

    public function findByDriver(string $driverId, array $filters = []): Collection
    {
        $query = Trip::with([
            'route',
            'vehicle:id,operator_id,plate_number,vehicle_type,seat_count',
            'vehicle.operator:id,user_id,company_name',
            'vehicle.operator.user:id,phone',
        ])->where('driver_id', $driverId);

        if (!empty($filters['date'])) {
            $query->whereDate('depart_at', $filters['date']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('depart_at')->get();
    }

    public function findByOperator(string $operatorId, array $filters = []): LengthAwarePaginator
    {
        $query = Trip::with(['route', 'vehicle:id,plate_number,vehicle_type,seat_count', 'driver:id,user_id', 'driver.user:id,full_name'])
                     ->whereHas('route', fn($q) => $q->where('operator_id', $operatorId));

        if (!empty($filters['date'])) {
            $query->whereDate('depart_at', $filters['date']);
        }

        // Lọc theo khoảng ngày (lưới lịch tuần của operator dùng range này)
        if (!empty($filters['date_from'])) {
            $query->whereDate('depart_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('depart_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sắp xếp tăng dần (chuyến sắp tới trước) + per_page cao để lưới lịch tuần
        // không bị cắt — 1 tuần có thể tới ~70 chuyến nên paginate(20) cũ làm mất chuyến.
        return $query->orderBy('depart_at')->paginate($filters['per_page'] ?? 100);
    }

    public function findAll(array $filters = []): Collection
    {
        $query = Trip::with([
            'route',
            'vehicle:id,operator_id,plate_number,vehicle_type,seat_count',
            'vehicle.operator:id,company_name',
            'driver:id,user_id,rating_avg,is_online',
            'driver.user:id,full_name,phone',
        ])
        ->withCount('bookings')
        // Tổng số khách đã đặt = sum passenger_count của booking còn giữ chỗ
        // (mọi trạng thái trừ cancelled/no_show — khớp với available_seats đã trừ khi đặt)
        ->withSum(['bookings as passengers_count' => fn ($q) => $q->whereNotIn(
            'booking_status', ['cancelled', 'no_show']
        )], 'passenger_count');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('depart_at', $filters['date']);
        }

        // Khoảng ngày (bộ lọc admin trips)
        if (!empty($filters['date_from'])) {
            $query->whereDate('depart_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('depart_at', '<=', $filters['date_to']);
        }

        // Tìm theo mã chuyến (tracking_code) hoặc tên thành phố tuyến
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('tracking_code', 'like', "%{$term}%")
                  ->orWhereHas('route', fn ($r) => $r
                      ->where('origin_city', 'like', "%{$term}%")
                      ->orWhere('dest_city', 'like', "%{$term}%"));
            });
        }

        return $query->orderByDesc('depart_at')->get();
    }

    public function create(array $data): Trip
    {
        return Trip::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return Trip::where('id', $id)->update($data) > 0;
    }

    public function getActiveTripsWithLocation(): Collection
    {
        return Trip::with(['driver', 'route'])
                   ->where('status', TripStatus::InProgress)
                   ->whereNotNull('driver_id')
                   ->get();
    }

    public function findInProgress(): Collection
    {
        return Trip::with([
            'route',
            'vehicle:id,plate_number,vehicle_type,seat_count',
            'driver:id,user_id,rating_avg,is_online,current_lat,current_lng',
            'driver.user:id,full_name,phone',
        ])
        ->whereIn('status', [TripStatus::Boarding, TripStatus::InProgress])
        ->orderBy('depart_at')
        ->get();
    }
}
