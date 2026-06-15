<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Trip $trip) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel("trips.{$this->trip->id}")];
    }

    public function broadcastAs(): string
    {
        return 'trip.started';
    }

    public function broadcastWith(): array
    {
        return [
            'started_at'   => $this->trip->started_at?->toIso8601String(),
            'driver_name'  => $this->trip->driver->user->full_name,
            'plate_number' => $this->trip->vehicle->plate_number,
        ];
    }
}
