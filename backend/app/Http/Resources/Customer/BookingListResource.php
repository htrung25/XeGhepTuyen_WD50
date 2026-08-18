<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'booking_status' => $this->booking_status->value,
            'payment_status' => $this->payment_status->value,
            'passenger_count' => $this->passenger_count,
            'final_amount' => $this->final_amount,
            'depart_at' => $this->trip->depart_at->format('Y-m-d H:i:s'),
            'route' => collect([$this->trip->route->origin_district, $this->trip->route->origin_city])->filter()->implode(', ')
                .' → '.collect([$this->trip->route->dest_district, $this->trip->route->dest_city])->filter()->implode(', '),
            'pickup_stop' => $this->pickupStop ? $this->pickupStop->stop_name : $this->pickup_address,
            'dropoff_stop' => $this->dropoffStop ? $this->dropoffStop->stop_name : $this->dropoff_address,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
