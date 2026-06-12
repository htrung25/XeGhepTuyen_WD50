<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'booking_code'     => $this->booking_code,
            'booking_status'   => $this->booking_status->value,
            'payment_status'   => $this->payment_status->value,
            'payment_method'   => $this->payment_method?->value,
            'contact_name'     => $this->contact_name,
            'contact_phone'    => $this->contact_phone,
            'passenger_count'  => $this->passenger_count,
            'base_amount'      => $this->subtotal,
            'discount_amount'  => $this->discount_amount,
            'final_amount'     => $this->final_amount,
            'pickup_address'   => $this->pickup_address,
            'dropoff_address'  => $this->dropoff_address,
            'expires_at'       => $this->expires_at?->format('Y-m-d H:i:s'),
            'qr_url'           => $this->qr_url,
            'trip'             => [
                'id'          => $this->trip->id,
                'depart_at'   => $this->trip->depart_at->format('Y-m-d H:i:s'),
                'arrive_at'   => $this->trip->arrive_at?->format('Y-m-d H:i:s'),
                'status'      => $this->trip->status->value,
                'route'       => "{$this->trip->route->origin_city} → {$this->trip->route->dest_city}",
                'vehicle'     => ['plate' => $this->trip->vehicle->plate_number, 'type' => $this->trip->vehicle->vehicle_type?->value],
                'driver_name' => $this->trip->driver?->user?->full_name,
                'driver_phone'=> $this->trip->driver?->user?->phone,
            ],
            'pickup_stop'      => ['stop_name' => $this->pickupStop->stop_name, 'stop_address' => $this->pickupStop->stop_address],
            'dropoff_stop'     => ['stop_name' => $this->dropoffStop->stop_name, 'stop_address' => $this->dropoffStop->stop_address],
            'passengers'       => $this->passengers->map(fn($p) => [
                'full_name'   => $p->full_name,
                'gender'      => $p->gender?->value,
                'seat_code'   => $p->seatMap?->seat_code,
            ]),
            'created_at'       => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
