<?php

namespace App\Http\Resources\Operator;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'status' => $this->status?->value,
            'depart_at' => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at' => $this->arrive_at?->format('Y-m-d H:i:s'),
            'base_price' => $this->price,
            'route' => [
                'id' => $this->route->id,
                'origin_city' => $this->route->origin_city,
                'dest_city' => $this->route->dest_city,
            ],
            'vehicle' => [
                'id' => $this->vehicle->id,
                'plate' => $this->vehicle->plate_number,
                'type' => $this->vehicle->vehicle_type?->value,
            ],
            'driver' => [
                'id' => $this->driver?->id,
                'full_name' => $this->driver?->user?->full_name,
                'phone' => $this->driver?->user?->phone,
            ],
            'booking_count' => $this->bookings()->whereIn('booking_status', ['confirmed', 'checked_in', 'completed'])->count(),
            // Số KHÁCH thật (tổng passenger_count) — cùng tập trạng thái với manifest để thẻ khớp danh sách khách
            'passengers_count' => (int) $this->bookings()->whereIn('booking_status', ['confirmed', 'checked_in', 'completed', 'no_show'])->sum('passenger_count'),
            'total_seats' => $this->vehicle->seat_count,
            'confirmed_revenue' => $this->bookings()->whereIn('booking_status', ['confirmed', 'checked_in', 'completed'])->sum('final_amount'),
            'notes' => $this->note,
        ];
    }
}
