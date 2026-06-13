<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tracking_code'   => $this->tracking_code,
            'status'          => $this->status->value,
            'depart_at'       => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at'       => $this->arrive_at?->format('Y-m-d H:i:s'),
            'price'           => $this->price,
            'available_seats' => $this->available_seats,
            'route'           => [
                'origin_city'    => $this->route->origin_city,
                'dest_city'      => $this->route->dest_city,
                'distance_km'    => $this->route->distance_km,
                'duration_min'   => $this->route->est_duration_min,
            ],
            'vehicle'         => [
                'id'           => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'vehicle_type' => $this->vehicle->vehicle_type?->value,
                'seat_count'   => $this->vehicle->seat_count,
            ],
            'operator'        => [
                'company_name' => $this->vehicle->operator?->company_name,
                'phone'        => $this->vehicle->operator?->user?->phone,
            ],
            'confirmed_count' => $this->bookings()->whereIn('booking_status', ['confirmed', 'checked_in'])->count(),
            // Tổng số khách đã đặt (sum passenger_count, trừ cancelled/no_show — khớp available_seats)
            'passengers_count'=> (int) $this->bookings()->whereNotIn('booking_status', ['cancelled', 'no_show'])->sum('passenger_count'),
            'total_seats'     => $this->vehicle->seat_count,
            'note'            => $this->note,
        ];
    }
}
