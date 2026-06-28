<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tracking_code'   => $this->tracking_code,
            'status'          => $this->status->value,
            'depart_at'       => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at'       => $this->arrive_at?->format('Y-m-d H:i:s'),
            'price'           => $this->price,
            'note'            => $this->note,
            'cancel_reason'   => $this->cancel_reason,
            'started_at'      => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at'    => $this->completed_at?->format('Y-m-d H:i:s'),
            'cancelled_at'    => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'route'           => [
                'id'          => $this->route->id,
                'name'        => $this->route->name ?? ($this->route->origin_city.' → '.$this->route->dest_city),
                'origin_city' => $this->route->origin_city,
                'dest_city'   => $this->route->dest_city,
                'stops'       => $this->whenLoaded('route', function () {
                    return $this->route->relationLoaded('stops')
                        ? $this->route->stops->map(fn ($s) => [
                            'id'             => $s->id,
                            'stop_name'      => $s->stop_name,
                            'address'        => $s->address,
                            'stop_order'     => $s->stop_order,
                            'offset_minutes' => $s->offset_minutes,
                        ])->sortBy('stop_order')->values()
                        : [];
                }),
            ],
            'vehicle'         => [
                'plate_number' => $this->vehicle->plate_number,
                'vehicle_type' => $this->vehicle->vehicle_type?->value,
                'seat_count'   => $this->vehicle->seat_count,
                'brand'        => $this->vehicle->brand ?? null,
                'model'        => $this->vehicle->model ?? null,
                'color'        => $this->vehicle->color ?? null,
            ],
            'operator'        => [
                'id'           => $this->vehicle->operator?->id,
                'company_name' => $this->vehicle->operator?->company_name,
            ],
            'driver'          => [
                'id'         => $this->driver?->id,
                'full_name'  => $this->driver?->user?->full_name,
                'phone'      => $this->driver?->user?->phone,
                'rating_avg' => $this->driver?->rating_avg,
                'is_online'  => $this->driver?->is_online,
            ],
            'booking_count'    => $this->bookings_count ?? 0,
            'passengers_count' => (int) ($this->passengers_count ?? 0),
            'total_seats'      => $this->vehicle->seat_count,
            'available_seats'  => $this->available_seats,
            'revenue'          => (double) ($this->completed_revenue ?? ($this->relationLoaded('bookings') ? $this->bookings->where('booking_status', 'completed')->sum('final_amount') : $this->bookings()->where('booking_status', 'completed')->sum('final_amount'))),
            'created_at'       => $this->created_at->format('Y-m-d H:i:s'),
            // Chỉ trả bookings list khi show() – whenLoaded tránh N+1 trên index
            'bookings'         => $this->whenLoaded('bookings', function () {
                return $this->bookings->map(fn ($b) => [
                    'id'              => $b->id,
                    'booking_code'    => $b->booking_code,
                    'booking_status'  => $b->booking_status->value ?? $b->booking_status,
                    'payment_status'  => $b->payment_status->value ?? $b->payment_status,
                    'payment_method'  => $b->payment_method->value ?? $b->payment_method,
                    'contact_name'    => $b->contact_name,
                    'contact_phone'   => $b->contact_phone,
                    'passenger_count' => $b->passenger_count,
                    'final_amount'    => $b->final_amount,
                    'pickup_stop'     => $b->pickupStop?->stop_name,
                    'dropoff_stop'    => $b->dropoffStop?->stop_name,
                    'created_at'      => $b->created_at->format('Y-m-d H:i:s'),
                ]);
            }),
        ];
    }
}
