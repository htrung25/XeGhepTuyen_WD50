<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'tracking_code' => $this->tracking_code,
            'status'        => $this->status->value,
            'depart_at'     => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at'     => $this->arrive_at?->format('Y-m-d H:i:s'),
            'base_price'    => $this->base_price,
            'route'         => [
                'origin_city'   => $this->route->origin_city,
                'dest_city'     => $this->route->dest_city,
                'distance_km'   => $this->route->distance_km,
                'duration_hours'=> $this->route->duration_hours,
                'stops'         => $this->route->stops->map(fn($s) => [
                    'id'          => $s->id,
                    'stop_name'   => $s->stop_name,
                    'stop_address'=> $s->stop_address,
                    'stop_order'  => $s->stop_order,
                    'is_pickup'   => $s->is_pickup,
                    'is_dropoff'  => $s->is_dropoff,
                ]),
            ],
            'vehicle'       => [
                'type'       => $this->vehicle->vehicle_type->value,
                'plate'      => $this->vehicle->plate_number,
                'brand'      => $this->vehicle->brand,
                'model'      => $this->vehicle->model,
                'seat_count' => $this->vehicle->seat_count,
                'amenities'  => $this->vehicle->amenities,
            ],
            'driver'        => [
                'full_name'  => $this->driver?->user?->full_name,
                'phone'      => $this->driver?->user?->phone,
                'rating_avg' => $this->driver?->rating_avg,
            ],
            'seat_map'      => $this->seatMaps->map(fn($s) => [
                'id'         => $s->id,
                'seat_code'  => $s->seat_code,
                'seat_type'  => $s->seat_type->value,
                'floor'      => $s->floor,
                'row'        => $s->row,
                'column'     => $s->column,
                'status'     => $s->status->value,
                'is_locked'  => $s->isLockExpired() ? false : ($s->status->value === 'locked'),
            ]),
        ];
    }
}
