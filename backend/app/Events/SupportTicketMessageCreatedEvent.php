<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketMessageCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'broadcasts';

    public function __construct(public readonly SupportMessage $message) {}

    public function broadcastOn(): array
    {
        $channel = $this->message->is_internal
            ? "admin.support.tickets.{$this->message->support_ticket_id}"
            : "support.tickets.{$this->message->support_ticket_id}";

        return [
            new PrivateChannel($channel),
            new PrivateChannel('admin.support'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.ticket.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'v' => 1,
            'type' => 'support_message.created',
            'payload' => [
                'ticket_id' => $this->message->support_ticket_id,
                'message' => [
                    'id' => $this->message->id,
                    'sender_id' => $this->message->sender_id,
                    'sender_type' => $this->message->sender_type,
                    'sender_name' => $this->message->sender_name,
                    'body' => $this->message->body,
                    'is_internal' => $this->message->is_internal,
                    'created_at' => $this->message->created_at?->toIso8601String(),
                ],
            ],
        ];
    }
}
