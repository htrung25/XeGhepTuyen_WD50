<?php

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'broadcasts';

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly array $changed,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("support.tickets.{$this->ticket->id}"),
            new PrivateChannel('admin.support'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.ticket.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'v' => 1,
            'type' => 'support_ticket.updated',
            'payload' => [
                'ticket_id' => $this->ticket->id,
                'status' => $this->ticket->status->value,
                'priority' => $this->ticket->priority->value,
                'assigned_to' => $this->ticket->assigned_to,
                'changed' => $this->changed,
                'updated_at' => $this->ticket->updated_at?->toIso8601String(),
            ],
        ];
    }
}
