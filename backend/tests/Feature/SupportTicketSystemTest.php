<?php

use App\Enums\TicketCategoryEnum;
use App\Enums\TicketPriorityEnum;
use App\Enums\TicketStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\AdminRole;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SupportTicket;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Laravel\Sanctum\Sanctum;

function setupSupportTestContext(): array
{
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $admin = User::factory()->create(['role' => UserRoleEnum::Admin, 'admin_role_id' => superAdminRole()->id]);

    // Create a booking for the customer to test booking association
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id,
        'company_name' => 'Xe Ghep Support Test Company',
        'business_license' => 'GP-'.rand(1000, 9999),
        'status' => 'verified',
    ]);
    $route = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hà Nội - Hải Phòng',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'base_price' => 150000,
    ]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '30A-'.rand(10000, 99999),
        'brand' => 'Ford',
        'model' => 'Transit',
        'vehicle_type' => 'van_9',
        'seat_count' => 9,
    ]);
    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-'.rand(100000, 999999),
        'license_class' => 'B2',
        'license_expiry' => now()->addYears(3),
        'id_card_number' => '999999999999',
        'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => now()->addDays(2),
        'arrive_at' => now()->addDays(2)->addHours(2),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);

    $stop1 = RouteStop::create([
        'route_id' => $route->id,
        'stop_name' => 'Mỹ Đình',
        'address' => 'Hà Nội',
        'lat' => 21.028511,
        'lng' => 105.782354,
        'stop_order' => 1,
        'is_pickup' => true,
        'is_dropoff' => false,
    ]);
    $stop2 = RouteStop::create([
        'route_id' => $route->id,
        'stop_name' => 'An Dương',
        'address' => 'Hải Phòng',
        'lat' => 20.844911,
        'lng' => 106.688084,
        'stop_order' => 2,
        'is_pickup' => false,
        'is_dropoff' => true,
    ]);

    $booking = Booking::create([
        'booking_code' => 'XG-'.rand(100000, 999999),
        'user_id' => $customer->id,
        'trip_id' => $trip->id,
        'pickup_stop_id' => $stop1->id,
        'dropoff_stop_id' => $stop2->id,
        'pickup_address' => '123 Pham Hung',
        'dropoff_address' => '456 Le Loi',
        'passenger_count' => 1,
        'contact_name' => $customer->full_name,
        'contact_phone' => '0901234567',
        'subtotal' => 150000,
        'final_amount' => 150000,
    ]);

    return [$customer, $admin, $booking];
}

it('allows customer to create a support ticket with a start message', function () {
    [$customer, $admin, $booking] = setupSupportTestContext();

    Sanctum::actingAs($customer);
    auth()->guard('customer')->setUser($customer);

    $payload = [
        'subject' => 'Tôi muốn phản hồi về dịch vụ',
        'category' => TicketCategoryEnum::Complaint->value,
        'booking_code' => $booking->booking_code,
        'message' => 'Tài xế đi xe rất cẩn thận nhưng bật nhạc quá to.',
    ];

    $response = $this->postJson('/api/customer/support/tickets', $payload);
    $response->assertStatus(210);

    $this->assertDatabaseHas('support_tickets', [
        'subject' => 'Tôi muốn phản hồi về dịch vụ',
        'category' => TicketCategoryEnum::Complaint->value,
        'ticket_code' => 'TK-000001',
        'user_id' => $customer->id,
    ]);

    $ticket = SupportTicket::first();
    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'sender_id' => $customer->id,
        'sender_type' => 'customer',
        'body' => 'Tài xế đi xe rất cẩn thận nhưng bật nhạc quá to.',
    ]);
});

it('prevents customer from linking ticket to a booking owned by someone else', function () {
    [$customer1, $admin, $booking1] = setupSupportTestContext();
    $customer2 = User::factory()->create(['role' => UserRoleEnum::Customer]);

    Sanctum::actingAs($customer2);
    auth()->guard('customer')->setUser($customer2);

    $payload = [
        'subject' => 'Hỗ trợ thanh toán lỗi',
        'category' => TicketCategoryEnum::Payment->value,
        'booking_code' => $booking1->booking_code, // Booking owned by customer1
        'message' => 'Tôi bị trừ tiền nhưng không nhận được vé.',
    ];

    $response = $this->postJson('/api/customer/support/tickets', $payload);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['booking_code']);
});

it('allows customer to view and reply to their own support ticket', function () {
    [$customer, $admin] = setupSupportTestContext();

    Sanctum::actingAs($customer);
    auth()->guard('customer')->setUser($customer);

    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-000002',
        'user_id' => $customer->id,
        'subject' => 'Hỏi về hoàn tiền',
        'category' => TicketCategoryEnum::Refund,
        'status' => TicketStatusEnum::Open,
    ]);

    // View ticket
    $viewResponse = $this->getJson("/api/customer/support/tickets/{$ticket->id}");
    $viewResponse->assertOk()
        ->assertJsonPath('data.subject', 'Hỏi về hoàn tiền');

    // Reply to ticket
    $replyResponse = $this->postJson("/api/customer/support/tickets/{$ticket->id}/reply", [
        'body' => 'Gửi tin nhắn phản hồi từ khách hàng.',
    ]);
    $replyResponse->assertOk();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'sender_id' => $customer->id,
        'sender_type' => 'customer',
        'body' => 'Gửi tin nhắn phản hồi từ khách hàng.',
    ]);
});

it('prevents customer from viewing or replying to someone elses ticket', function () {
    [$customer1, $admin] = setupSupportTestContext();
    $customer2 = User::factory()->create(['role' => UserRoleEnum::Customer]);

    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-000003',
        'user_id' => $customer1->id,
        'subject' => 'Lỗi ứng dụng',
        'category' => TicketCategoryEnum::Technical,
        'status' => TicketStatusEnum::Open,
    ]);

    Sanctum::actingAs($customer2);
    auth()->guard('customer')->setUser($customer2);

    // Try view
    $this->getJson("/api/customer/support/tickets/{$ticket->id}")->assertStatus(403);

    // Try reply
    $this->postJson("/api/customer/support/tickets/{$ticket->id}/reply", [
        'body' => 'Tin tặc cố phản hồi.',
    ])->assertStatus(403);
});

it('allows admin to reply to a support ticket, changing its status to in_progress', function () {
    [$customer, $admin] = setupSupportTestContext();

    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-000004',
        'user_id' => $customer->id,
        'subject' => 'Hỏi về tài khoản',
        'category' => TicketCategoryEnum::Other,
        'status' => TicketStatusEnum::Open,
    ]);

    Sanctum::actingAs($admin);
    auth()->guard('admin')->setUser($admin);

    $response = $this->postJson("/api/admin/support/tickets/{$ticket->id}/reply", [
        'body' => 'Chào bạn, chúng tôi đang kiểm tra yêu cầu của bạn.',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'reply_support_ticket',
        'model_id' => $ticket->id,
    ]);
    $response->assertOk();

    $this->assertDatabaseHas('support_messages', [
        'support_ticket_id' => $ticket->id,
        'sender_id' => $admin->id,
        'sender_type' => 'admin',
        'body' => 'Chào bạn, chúng tôi đang kiểm tra yêu cầu của bạn.',
    ]);

    // Verify ticket status changed to in_progress
    expect(SupportTicket::find($ticket->id)->status)->toBe(TicketStatusEnum::InProgress);
});

it('allows admin to list, search, filter tickets, and retrieve statistics', function () {
    [$customer, $admin] = setupSupportTestContext();

    SupportTicket::create([
        'ticket_code' => 'TK-000005',
        'user_id' => $customer->id,
        'subject' => 'Connection error problem',
        'category' => TicketCategoryEnum::Technical,
        'status' => TicketStatusEnum::Open,
        'priority' => TicketPriorityEnum::High,
    ]);

    SupportTicket::create([
        'ticket_code' => 'TK-000006',
        'user_id' => $customer->id,
        'subject' => 'Policy information request',
        'category' => TicketCategoryEnum::General,
        'status' => TicketStatusEnum::Closed,
        'priority' => TicketPriorityEnum::Low,
    ]);

    Sanctum::actingAs($admin);
    auth()->guard('admin')->setUser($admin);

    // List all
    $response = $this->getJson('/api/admin/support/tickets');
    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('stats.open', 1)
        ->assertJsonPath('stats.closed', 1);

    // Filter by status
    $responseFilter = $this->getJson('/api/admin/support/tickets?status=open');
    $responseFilter->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ticket_code', 'TK-000005');

    // Search subject (using ASCII term 'policy')
    $responseSearch = $this->getJson('/api/admin/support/tickets?search=policy');
    $responseSearch->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ticket_code', 'TK-000006');
});

it('ghi audit khi admin phân công, giải quyết và đóng ticket', function () {
    [$customer, $admin] = setupSupportTestContext();
    $assignee = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);
    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-000007',
        'user_id' => $customer->id,
        'subject' => 'Cần hỗ trợ audit workflow',
        'category' => TicketCategoryEnum::Other,
        'status' => TicketStatusEnum::Open,
    ]);

    Sanctum::actingAs($admin);
    auth()->guard('admin')->setUser($admin);

    $this->postJson("/api/admin/support/tickets/{$ticket->id}/assign", [
        'assigned_to' => $assignee->id,
    ])->assertOk();
    $this->postJson("/api/admin/support/tickets/{$ticket->id}/resolve")->assertOk();
    $this->postJson("/api/admin/support/tickets/{$ticket->id}/close")->assertOk();

    foreach (['assign_support_ticket', 'resolve_support_ticket', 'close_support_ticket'] as $action) {
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => $action,
            'model_id' => $ticket->id,
        ]);
    }
});

it('chặn admin không có quyền support ticket', function () {
    $role = AdminRole::create([
        'name' => 'Không có quyền hỗ trợ',
        'slug' => 'no-support-access',
        'permissions' => ['dashboard.view'],
        'is_super' => false,
    ]);
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => $role->id,
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/support/tickets')->assertForbidden();
    $this->postJson('/api/admin/support/tickets/00000000-0000-0000-0000-000000000000/close')->assertForbidden();
});

it('cho phép admin thường có quyền support xem và xử lý ticket', function () {
    [$customer] = setupSupportTestContext();
    $role = AdminRole::create([
        'name' => 'CSKH test',
        'slug' => 'support-operator',
        'permissions' => ['support_tickets.view', 'support_tickets.manage'],
        'is_super' => false,
    ]);
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => $role->id,
    ]);
    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-000008',
        'user_id' => $customer->id,
        'subject' => 'Ticket cho admin thường',
        'category' => TicketCategoryEnum::Other,
        'status' => TicketStatusEnum::Open,
    ]);

    Sanctum::actingAs($admin);
    auth()->guard('admin')->setUser($admin);

    $this->getJson('/api/admin/support/tickets')->assertOk();
    $this->postJson("/api/admin/support/tickets/{$ticket->id}/close")->assertOk();

    expect($ticket->refresh()->status)->toBe(TicketStatusEnum::Closed);
});

it('admin thường chỉ có quyền xem không được xử lý ticket', function () {
    [$customer] = setupSupportTestContext();
    $role = AdminRole::create([
        'name' => 'Chỉ xem support',
        'slug' => 'support-viewer',
        'permissions' => ['support_tickets.view'],
        'is_super' => false,
    ]);
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => $role->id,
    ]);
    $ticket = SupportTicket::create([
        'ticket_code' => 'TK-000009',
        'user_id' => $customer->id,
        'subject' => 'Ticket chỉ được xem',
        'category' => TicketCategoryEnum::Other,
        'status' => TicketStatusEnum::Open,
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/support/tickets')->assertOk();
    $this->postJson("/api/admin/support/tickets/{$ticket->id}/close")->assertForbidden();
});
