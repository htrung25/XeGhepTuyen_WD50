<?php

namespace App\Services;

use App\Events\DriverLocationUpdated;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrackingService
{
    private const LOCATION_TTL = 30; // giây

    public function updateLocation(Driver $driver, float $lat, float $lng): void
    {
        $driver->update([
            'current_lat'         => $lat,
            'current_lng'         => $lng,
            'location_updated_at' => now(),
        ]);

        Cache::put(
            "driver_location:{$driver->id}",
            json_encode(['lat' => $lat, 'lng' => $lng, 'updated_at' => now()->toIso8601String()]),
            self::LOCATION_TTL
        );

        $activeTrip = $driver->trips()
                             ->whereIn('status', ['boarding', 'in_progress'])
                             ->first();

        if ($activeTrip) {
            $etaMinutes = $this->calculateEta($lat, $lng, $activeTrip);
            event(new DriverLocationUpdated($activeTrip, $lat, $lng, $etaMinutes));
        }
    }

    public function getLocation(Driver $driver): ?array
    {
        $cached = Cache::get("driver_location:{$driver->id}");
        return $cached ? json_decode($cached, true) : null;
    }

    private function calculateEta(float $lat, float $lng, Trip $trip): ?int
    {
        $apiKey = config('services.google_maps.key');
        if (!$apiKey) {
            return null;
        }

        $nextStop = $trip->bookings()
                         ->whereIn('booking_status', ['confirmed'])
                         ->with('pickupStop')
                         ->first()
                         ?->pickupStop;

        if (!$nextStop) {
            return null;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins'      => "{$lat},{$lng}",
                'destinations' => "{$nextStop->lat},{$nextStop->lng}",
                'key'          => $apiKey,
                'mode'         => 'driving',
            ]);

            $data = $response->json();
            $seconds = $data['rows'][0]['elements'][0]['duration']['value'] ?? null;

            return $seconds ? (int) ceil($seconds / 60) : null;
        } catch (\Exception) {
            return null;
        }
    }
}
