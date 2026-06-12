<?php

namespace App\Http\Resources\Customer;

use App\Enums\SeatStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tracking_code'   => $this->tracking_code,
            'depart_at'       => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at'       => $this->arrive_at?->format('Y-m-d H:i:s'),
            'price'           => $this->price,
            'route'           => [
                'origin_city' => $this->route->origin_city,
                'dest_city'   => $this->route->dest_city,
                'distance_km' => $this->route->distance_km,
            ],
            'operator'        => [
                'company_name' => $this->route->operator?->company_name,
            ],
            'vehicle'         => [
                'vehicle_type' => $this->vehicle->vehicle_type->value,
                'seat_count'   => $this->vehicle->seat_count,
                'amenities'    => $this->vehicle->amenities,
            ],
            'driver'          => $this->driver ? [
                'full_name'  => $this->driver->user?->full_name,
                'rating_avg' => (float) $this->driver->rating_avg,
                'total_trips'=> (int) $this->driver->total_trips,
            ] : null,
            'available_seats' => $this->seatMaps->where('status', SeatStatus::Available)->count(),
            'total_seats'     => $this->vehicle->seat_count,
            'pickup_stops'    => $this->route->stops->where('is_pickup', true)->values()->map(fn($s) => [
                'id'         => $s->id,
                'stop_name'  => $s->stop_name,
                'stop_order' => $s->stop_order,
            ]),
            'dropoff_stops'   => $this->route->stops->where('is_dropoff', true)->values()->map(fn($s) => [
                'id'         => $s->id,
                'stop_name'  => $s->stop_name,
                'stop_order' => $s->stop_order,
            ]),
        ];
    }
}
