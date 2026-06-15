<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Trip $trip,
        public readonly float $lat,
        public readonly float $lng,
        public readonly ?int $etaMinutes,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("trips.{$this->trip->id}"),
            new PrivateChannel('admin.monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'driver.location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'lat'         => $this->lat,
            'lng'         => $this->lng,
            'updated_at'  => now()->toIso8601String(),
            'eta_minutes' => $this->etaMinutes,
        ];
    }
}
