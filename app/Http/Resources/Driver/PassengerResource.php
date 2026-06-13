<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassengerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_id'     => $this->id,
            'booking_code'   => $this->booking_code,
            'booking_status' => $this->booking_status->value,
            'contact_name'   => $this->contact_name,
            'contact_phone'  => $this->contact_phone,
            'passenger_count'=> $this->passenger_count,
            'payment_method' => $this->payment_method?->value,
            'payment_status' => $this->payment_status?->value,
            // Số tiền tài xế cần thu (chỉ khi tiền mặt & chưa thanh toán)
            'amount_due'     => ($this->payment_method?->value === 'cash'
                && $this->payment_status?->value === 'unpaid')
                ? (int) $this->final_amount : 0,
            'pickup_stop'    => [
                'stop_name'    => $this->pickupStop->stop_name,
                'stop_address' => $this->pickupStop->stop_address,
            ],
            'dropoff_stop'   => [
                'stop_name'    => $this->dropoffStop->stop_name,
                'stop_address' => $this->dropoffStop->stop_address,
            ],
            'pickup_address' => $this->pickup_address,
            'passengers'     => $this->passengers->map(fn($p) => [
                'full_name' => $p->full_name,
                'seat_code' => $p->seatMap?->seat_code,
            ]),
            'checked_in'     => $this->booking_status->value === 'checked_in',
            'qr_token'       => $this->qr_token,
        ];
    }
}
