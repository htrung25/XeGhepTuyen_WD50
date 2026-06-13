<?php

namespace App\Http\Resources\Operator;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tracking_code'   => $this->booking_code,
            'status'          => $this->mapStatus(),
            'payment_status'  => $this->payment_status->value,
            'passenger_name'  => $this->contact_name,
            'passenger_phone' => $this->contact_phone,
            'passenger_count' => $this->passenger_count,
            'total_amount'    => (int) $this->final_amount,
            'seat_codes'      => $this->passengers->map(fn ($p) => $p->seatMap?->seat_code)->filter()->values(),
            'pickup_stop'     => ['stop_name' => $this->pickupStop?->stop_name],
            'dropoff_stop'    => ['stop_name' => $this->dropoffStop?->stop_name],
            'trip'            => [
                'tracking_code' => $this->trip->tracking_code,
                'depart_at'     => $this->trip->depart_at->format('Y-m-d H:i:s'),
                'route'         => [
                    'origin_city' => $this->trip->route->origin_city,
                    'dest_city'   => $this->trip->route->dest_city,
                ],
            ],
            'created_at'      => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Map enum booking_status → nhóm trạng thái FE operator hiển thị.
     */
    private function mapStatus(): string
    {
        return match ($this->booking_status->value) {
            'pending'                  => 'pending_payment',
            'confirmed', 'checked_in'  => 'confirmed',
            'completed'                => 'completed',
            'cancelled', 'no_show'     => 'cancelled',
            default                    => $this->booking_status->value,
        };
    }
}
