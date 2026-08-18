<?php

namespace App\Http\Controllers\Operator;

use App\Enums\TripStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function map(): JsonResponse
    {
        $operatorId = auth('operator')->user()->operator->id;

        $trips = Trip::query()
            ->where('status', TripStatusEnum::InProgress)
            ->whereHas('route', fn ($query) => $query->where('operator_id', $operatorId))
            ->with([
                'route:id,operator_id,origin_city,origin_district,dest_city,dest_district',
                'vehicle:id,plate_number',
                'driver:id,user_id,current_lat,current_lng,location_updated_at',
                'driver.user:id,full_name',
            ])
            ->orderBy('depart_at')
            ->get()
            ->map(fn (Trip $trip) => [
                'id' => $trip->id,
                'tracking_code' => $trip->tracking_code,
                'vehicle_plate' => $trip->vehicle?->plate_number,
                'driver_name' => $trip->driver?->user?->full_name,
                'lat' => (float) ($trip->driver?->current_lat ?? 0),
                'lng' => (float) ($trip->driver?->current_lng ?? 0),
                'location_updated_at' => $trip->driver?->location_updated_at?->toIso8601String(),
                'route' => [
                    'origin_city' => $trip->route?->origin_city,
                    'origin_district' => $trip->route?->origin_district,
                    'dest_city' => $trip->route?->dest_city,
                    'dest_district' => $trip->route?->dest_district,
                ],
            ]);

        return response()->json(['success' => true, 'data' => $trips]);
    }
}
