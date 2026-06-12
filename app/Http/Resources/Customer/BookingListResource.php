<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'booking_code'    => $this->booking_code,
            'booking_status'  => $this->booking_status->value,
            'payment_status'  => $this->payment_status->value,
            'passenger_count' => $this->passenger_count,
            'final_amount'    => $this->final_amount,
            'depart_at'       => $this->trip->depart_at->format('Y-m-d H:i:s'),
            'route'           => "{$this->trip->route->origin_city} → {$this->trip->route->dest_city}",
            'pickup_stop'     => $this->pickupStop->stop_name,
            'created_at'      => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
