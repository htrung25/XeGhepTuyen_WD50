<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'tracking_code'  => $this->tracking_code,
            'status'         => $this->status->value,
            'depart_at'      => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at'      => $this->arrive_at?->format('Y-m-d H:i:s'),
            'price'          => $this->price,
            'route'          => [
                'origin_city' => $this->route->origin_city,
                'dest_city'   => $this->route->dest_city,
            ],
            'vehicle'        => [
                'plate_number' => $this->vehicle->plate_number,
                'vehicle_type' => $this->vehicle->vehicle_type?->value,
                'seat_count'   => $this->vehicle->seat_count,
            ],
            'operator'       => [
                'company_name' => $this->vehicle->operator?->company_name,
            ],
            'driver'         => [
                'full_name'  => $this->driver?->user?->full_name,
                'phone'      => $this->driver?->user?->phone,
                'rating_avg' => $this->driver?->rating_avg,
                'is_online'  => $this->driver?->is_online,
            ],
            'booking_count'   => $this->bookings_count ?? 0,
            'passengers_count'=> (int) ($this->passengers_count ?? 0),
            'total_seats'     => $this->vehicle->seat_count,
            'available_seats' => $this->available_seats,
            'revenue'        => $this->bookings()->where('booking_status', 'completed')->sum('final_amount'),
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
