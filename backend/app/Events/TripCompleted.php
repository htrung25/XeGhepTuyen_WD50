<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Trip $trip) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel("trips.{$this->trip->id}")];
    }

    public function broadcastAs(): string
    {
        return 'trip.completed';
    }

    public function broadcastWith(): array
    {
        return ['completed_at' => $this->trip->completed_at?->toIso8601String()];
    }
}
