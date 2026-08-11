<?php

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'broadcasts';

    public function __construct(public readonly SupportTicket $ticket) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.support')];
    }

    public function broadcastAs(): string
    {
        return 'support.ticket.created';
    }

    public function broadcastWith(): array
    {
        return [
            'v' => 1,
            'type' => 'support_ticket.created',
            'payload' => [
                'ticket' => [
                    'id' => $this->ticket->id,
                    'ticket_code' => $this->ticket->ticket_code,
                    'user_id' => $this->ticket->user_id,
                    'subject' => $this->ticket->subject,
                    'category' => $this->ticket->category->value,
                    'priority' => $this->ticket->priority->value,
                    'status' => $this->ticket->status->value,
                    'message_count' => $this->ticket->messages_count ?? 1,
                    'created_at' => $this->ticket->created_at?->toIso8601String(),
                ],
            ],
        ];
    }
}
