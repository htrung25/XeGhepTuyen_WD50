<?php

use App\Enums\UserRole;
use App\Models\AdminRole;
use App\Models\Notification;
use App\Models\User;
use App\Services\PartnerApplicationService;
use Laravel\Sanctum\Sanctum;

/** Thông báo admin (fan-out theo quyền RBAC). Helpers ở tests/Pest.php. */
function partnerAppData(): array
{
    return [
        'company_name' => 'NX Thông Báo',
        'tax_code' => '0100009999',
        'address' => 'Hà Nội',
        'fleet_breakdown' => ['sedan_4' => 1, 'mpv_7' => 0, 'van_9' => 0, 'minibus_16' => 0],
        'representative_name' => 'Người ĐD',
        'phone' => '0911119999',
        'email' => 'partner.notif@xeghep.vn',
    ];
}

it('thông báo admin có quyền khi có đơn đăng ký đối tác mới', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);

    app(PartnerApplicationService::class)->submit(partnerAppData());

    $this->assertDatabaseHas('notifications', [
        'user_id' => $admin->id,
        'type' => 'system',
    ]);
    $n = Notification::where('user_id', $admin->id)->first();
    expect($n->data['kind'])->toBe('partner_application');
    expect($n->data['link'])->toBe('/admin/operators');
});

it('KHÔNG thông báo admin thiếu quyền partner_applications.review', function () {
    $role = AdminRole::create([
        'name' => 'Kế toán', 'slug' => 'ketoan-notif',
        'permissions' => ['finance.view'], 'is_super' => false,
    ]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'admin_role_id' => $role->id]);

    app(PartnerApplicationService::class)->submit(partnerAppData());

    $this->assertDatabaseMissing('notifications', ['user_id' => $admin->id]);
});

it('thông báo admin finance.payout khi nhà xe yêu cầu quyết toán', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);
    $operator = makeOperatorWithRevenue(online: 1, cash: 0); // available = 135.000
    $headers = ['Authorization' => 'Bearer '.$operator->user->createToken('operator_token')->plainTextToken];

    $this->postJson('/api/operator/revenue/payout-request', [], $headers)->assertCreated();

    $n = Notification::where('user_id', $admin->id)->first();
    expect($n)->not->toBeNull();
    expect($n->data['kind'])->toBe('payout_request');
});

it('admin xem danh sách + unread_count + đánh dấu đã đọc', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);
    Notification::create([
        'user_id' => $admin->id, 'type' => 'system', 'channel' => 'in_app',
        'title' => 'T', 'body' => 'B', 'is_read' => false, 'sent_at' => now(),
    ]);
    Sanctum::actingAs($admin);

    $res = $this->getJson('/api/admin/notifications')
        ->assertOk()
        ->assertJsonPath('meta.unread_count', 1)
        ->assertJsonCount(1, 'data');

    $id = $res->json('data.0.id');
    $this->putJson("/api/admin/notifications/{$id}/read")->assertOk();

    $this->getJson('/api/admin/notifications')->assertJsonPath('meta.unread_count', 0);
});
