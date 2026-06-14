<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalSeats     = (int) ($this->vehicle?->seat_count ?? 0);
        $availableSeats = (int) ($this->available_seats ?? 0);
        $arriveAt       = $this->arrive_at;
        $isDelayed      = $arriveAt ? now()->greaterThan($arriveAt) : false;
        $etaMinutes     = ($arriveAt && now()->lessThan($arriveAt))
            ? now()->diffInMinutes($arriveAt)
            : 0;

        return [
            'id'             => $this->id,
            'trip_code'      => $this->tracking_code,
            'route_name'     => trim(($this->route?->origin_city ?? '') . ' → ' . ($this->route?->dest_city ?? '')),
            'driver_name'    => $this->driver?->user?->full_name ?? '—',
            'driver_rating'  => (float) ($this->driver?->rating_avg ?? 0),
            'vehicle_type'   => $this->vehicle?->vehicle_type?->label() ?? '—',
            'vehicle_plate'  => $this->vehicle?->plate_number ?? '—',
            'departure_time' => $this->depart_at?->format('H:i') ?? '',
            'passenger_count'=> max(0, $totalSeats - $availableSeats),
            'total_seats'    => $totalSeats,
            'status'         => $this->status?->value,
            'current_speed'  => 0,
            'eta_minutes'    => (int) $etaMinutes,
            'lat'            => (float) ($this->driver?->current_lat ?? 0),
            'lng'            => (float) ($this->driver?->current_lng ?? 0),
            'is_delayed'     => $isDelayed,
        ];
    }
}
