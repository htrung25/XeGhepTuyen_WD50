<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'trip_id'         => $this->id,
            'tracking_code'   => $this->tracking_code,
            'depart_at'       => $this->depart_at->format('Y-m-d H:i:s'),
            'completed_at'    => $this->completed_at?->format('Y-m-d H:i:s'),
            'route'           => "{$this->route->origin_city} → {$this->route->dest_city}",
            'passenger_count' => $this->bookings()->where('booking_status', 'completed')->sum('passenger_count'),
            'revenue'         => $this->bookings()->where('booking_status', 'completed')->sum('final_amount'),
        ];
    }
}
