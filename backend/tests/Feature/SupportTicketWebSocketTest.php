<?php

use App\Enums\TicketStatusEnum;
use App\Events\SupportTicketCreatedEvent;
use App\Events\SupportTicketMessageCreatedEvent;
use App\Events\SupportTicketUpdatedEvent;
use App\Models\AdminRole;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SupportTicketService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app-id',
        'broadcasting.connections.pusher.options.cluster' => 'ap1',
    ]);
    require base_path('routes/channels.php');
});

function websocketTicketContext(): array
{
    $customer = User::factory()->create(['role' => 'customer']);
    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-WS-001',
        'user_id' => $customer->id,
        'subject' => 'Kiểm tra WebSocket ticket',
        'category' => 'technical',
        'priority' => 'normal',
        'status' => 'open',
    ]);

    return [$customer, $ticket];
}

it('phát message public vào đúng private room với contract có version type payload', function () {
    [$customer, $ticket] = websocketTicketContext();
    $message = SupportMessage::create([
        'support_ticket_id' => $ticket->id,
        'sender_id' => $customer->id,
        'sender_type' => 'customer',
        'sender_name' => $customer->full_name,
        'body' => 'Xin hỗ trợ realtime',
        'is_internal' => false,
    ]);

    $event = new SupportTicketMessageCreatedEvent($message);

    expect($event->broadcastAs())->toBe('support.ticket.message.created')
        ->and($event->queue)->toBe('broadcasts')
        ->and($event->broadcastOn()[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($event->broadcastOn()[0]->name)->toBe("private-support.tickets.{$ticket->id}")
        ->and($event->broadcastOn()[1]->name)->toBe('private-admin.support')
        ->and($event->broadcastWith()['v'])->toBe(1)
        ->and($event->broadcastWith()['type'])->toBe('support_message.created')
        ->and($event->broadcastWith()['payload']['message']['body'])->toBe('Xin hỗ trợ realtime');
});

it('cô lập ghi chú nội bộ sang room chỉ dành cho admin', function () {
    [$customer, $ticket] = websocketTicketContext();
    $message = SupportMessage::create([
        'support_ticket_id' => $ticket->id,
        'sender_id' => $customer->id,
        'sender_type' => 'admin',
        'sender_name' => 'CSKH',
        'body' => 'Ghi chú nội bộ',
        'is_internal' => true,
    ]);

    $event = new SupportTicketMessageCreatedEvent($message);

    expect(array_map(fn ($channel) => $channel->name, $event->broadcastOn()))
        ->toBe([
            "private-admin.support.tickets.{$ticket->id}",
            'private-admin.support',
        ]);
});

it('phát ticket mới và thay đổi trạng thái vào feed admin và room ticket', function () {
    [, $ticket] = websocketTicketContext();

    $created = new SupportTicketCreatedEvent($ticket->loadCount('messages'));
    $updated = new SupportTicketUpdatedEvent($ticket, ['status']);

    expect($created->broadcastOn()[0]->name)->toBe('private-admin.support')
        ->and($created->broadcastWith()['type'])->toBe('support_ticket.created')
        ->and($updated->broadcastAs())->toBe('support.ticket.updated')
        ->and(array_map(fn ($channel) => $channel->name, $updated->broadcastOn()))
        ->toBe([
            "private-support.tickets.{$ticket->id}",
            'private-admin.support',
        ]);
});

it('chỉ cho chủ ticket và admin có quyền xác thực room hội thoại', function () {
    [$customer, $ticket] = websocketTicketContext();
    $otherCustomer = User::factory()->create(['role' => 'customer']);
    $role = AdminRole::create([
        'name' => 'CSKH WebSocket',
        'slug' => 'cskh-websocket',
        'permissions' => ['support_tickets.view'],
    ]);
    $admin = User::factory()->create([
        'role' => 'admin',
        'admin_role_id' => $role->id,
    ]);
    $payload = [
        'socket_id' => '123.456',
        'channel_name' => "private-support.tickets.{$ticket->id}",
    ];

    expect(SupportTicket::whereKey($ticket->id)->where('user_id', $customer->id)->exists())->toBeTrue();
    $this->withToken($customer->createToken('ws-owner')->plainTextToken)
        ->postJson('/api/broadcasting/auth', $payload)->assertOk();
    auth()->forgetGuards();
    $this->withToken($otherCustomer->createToken('ws-other')->plainTextToken)
        ->postJson('/api/broadcasting/auth', $payload)->assertForbidden();
    auth()->forgetGuards();
    $this->withToken($admin->createToken('ws-admin')->plainTextToken)
        ->postJson('/api/broadcasting/auth', $payload)->assertOk();
});

it('không cho customer xác thực room ghi chú nội bộ hoặc feed admin', function () {
    [$customer, $ticket] = websocketTicketContext();

    $this->withToken($customer->createToken('ws-customer')->plainTextToken)
        ->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-admin.support.tickets.{$ticket->id}",
        ])->assertForbidden();

    $this->postJson('/api/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-admin.support',
    ])->assertForbidden();
});

it('giới hạn tần suất endpoint xác thực broadcasting', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($route) => $route->uri() === 'api/broadcasting/auth',
    );

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:60,1');
});

it('service phát event realtime sau khi ghi dữ liệu ticket thành công', function () {
    Event::fake([
        SupportTicketCreatedEvent::class,
        SupportTicketMessageCreatedEvent::class,
        SupportTicketUpdatedEvent::class,
    ]);
    $customer = User::factory()->create(['role' => 'customer']);
    $service = app(SupportTicketService::class);

    $ticket = $service->createTicket($customer, [
        'subject' => 'Ticket realtime từ service',
        'category' => 'technical',
        'priority' => 'normal',
        'message' => 'Tin nhắn đầu tiên',
    ]);
    $service->replyAsCustomer($customer, $ticket->id, 'Tin nhắn tiếp theo');
    $service->updateTicket($ticket->id, status: TicketStatusEnum::InProgress);

    Event::assertDispatched(SupportTicketCreatedEvent::class, fn ($event) => $event->ticket->is($ticket));
    Event::assertDispatched(SupportTicketMessageCreatedEvent::class, fn ($event) => $event->message->body === 'Tin nhắn tiếp theo');
    Event::assertDispatched(SupportTicketUpdatedEvent::class, fn ($event) => in_array('status', $event->changed, true));
});
