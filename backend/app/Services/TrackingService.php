<?php

namespace App\Services;

use App\Events\DriverLocationUpdatedEvent;
use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TrackingService
{
    private const LOCATION_TTL = 120; // giây — chịu được vài nhịp GPS bị mất mạng

    public function updateLocation(Driver $driver, Trip $trip, float $lat, float $lng): void
    {
        $updatedAt = now();
        $etaMinutes = $this->calculateEta($lat, $lng, $trip);

        $driver->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
            'location_updated_at' => $updatedAt,
        ]);

        Cache::put(
            "driver_location:{$driver->id}",
            [
                'lat' => $lat,
                'lng' => $lng,
                'updated_at' => $updatedAt->toIso8601String(),
                'eta_minutes' => $etaMinutes,
            ],
            self::LOCATION_TTL
        );

        event(new DriverLocationUpdatedEvent($trip, $lat, $lng, $etaMinutes));
    }

    public function getLocation(Driver $driver): ?array
    {
        $cached = Cache::get("driver_location:{$driver->id}");
        if (is_array($cached)) {
            return $cached;
        }

        // Tương thích cache cũ dạng JSON trong thời gian rolling deploy.
        if (is_string($cached)) {
            return json_decode($cached, true);
        }

        if ($driver->location_updated_at?->gt(now()->subSeconds(self::LOCATION_TTL))) {
            return [
                'lat' => (float) $driver->current_lat,
                'lng' => (float) $driver->current_lng,
                'updated_at' => $driver->location_updated_at->toIso8601String(),
                'eta_minutes' => null,
            ];
        }

        return null;
    }

    private function calculateEta(float $lat, float $lng, Trip $trip): ?int
    {
        $apiKey = config('services.google_maps.api_key');
        if (! $apiKey) {
            return null;
        }

        $nextBooking = $trip->bookings()
            ->with('pickupStop')
            ->whereIn('booking_status', ['confirmed'])
            ->first();

        $pickupLat = $nextBooking?->pickup_lat ?? $nextBooking?->pickupStop?->lat;
        $pickupLng = $nextBooking?->pickup_lng ?? $nextBooking?->pickupStop?->lng;
        if (! $nextBooking || $pickupLat === null || $pickupLng === null) {
            return null;
        }

        try {
            // Timeout ngắn: đây là call trong request đồng bộ của khách xem tracking —
            // Google chậm không được phép treo cả API.
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->get(rtrim((string) config('services.google_maps.base_url'), '/').'/distancematrix/json', [
                    'origins' => "{$lat},{$lng}",
                    'destinations' => "{$pickupLat},{$pickupLng}",
                    'key' => $apiKey,
                    'mode' => 'driving',
                ]);

            $response->throw();
            $data = $response->json();
            if (($data['status'] ?? null) !== 'OK'
                || ($data['rows'][0]['elements'][0]['status'] ?? null) !== 'OK') {
                return null;
            }

            $seconds = $data['rows'][0]['elements'][0]['duration']['value'] ?? null;

            return $seconds ? (int) ceil($seconds / 60) : null;
        } catch (\Exception) {
            return null;
        }
    }
}
