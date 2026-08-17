<?php

namespace App\Http\Resources\Customer;

use App\Enums\SeatStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TripSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_code' => $this->tracking_code,
            'depart_at' => $this->depart_at->format('Y-m-d H:i:s'),
            'arrive_at' => $this->arrive_at?->format('Y-m-d H:i:s'),
            'price' => $this->price,
            'route' => [
                'origin_city' => $this->route->origin_city,
                'origin_district' => $this->route->origin_district,
                'dest_city' => $this->route->dest_city,
                'dest_district' => $this->route->dest_district,
                'distance_km' => $this->route->distance_km,
                'pickup_service_area' => $this->serviceAreaPayload($this->route->pickupServiceArea),
                'dropoff_service_area' => $this->serviceAreaPayload($this->route->dropoffServiceArea),
            ],
            'operator' => [
                'company_name' => $this->route->operator?->company_name,
            ],
            'vehicle' => [
                'vehicle_type' => $this->vehicle->vehicle_type->value,
                'seat_count' => $this->vehicle->seat_count,
                'amenities' => $this->vehicle->amenities,
            ],
            'driver' => $this->driver ? [
                'full_name' => $this->driver->user?->full_name,
                'rating_avg' => (float) $this->driver->rating_avg,
                'total_trips' => (int) $this->driver->total_trips,
            ] : null,
            'available_seats' => $this->seatMaps->where('status', SeatStatusEnum::Available)->count(),
            'total_seats' => $this->vehicle->seat_count,
            'pickup_stops' => $this->route->stops->where('is_pickup', true)->values()->map(fn ($s) => [
                'id' => $s->id,
                'stop_name' => $s->stop_name,
                'stop_order' => $s->stop_order,
            ]),
            'dropoff_stops' => $this->route->stops->where('is_dropoff', true)->values()->map(fn ($s) => [
                'id' => $s->id,
                'stop_name' => $s->stop_name,
                'stop_order' => $s->stop_order,
            ]),
        ];
    }

    private function serviceAreaPayload($area): ?array
    {
        if (! $area) {
            return null;
        }

        $boundary = null;
        if (DB::getDriverName() === 'mysql') {
            $row = DB::selectOne('select ST_AsGeoJSON(boundary) as geojson from service_areas where id = ?', [$area->id]);
            $boundary = $row?->geojson ? json_decode($row->geojson, true) : null;
        }

        return [
            'code' => $area->code,
            'name' => $area->name,
            'boundary' => $boundary,
        ];
    }
}
