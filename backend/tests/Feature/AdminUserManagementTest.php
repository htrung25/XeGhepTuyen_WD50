<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/** Quản lý người dùng (admin): chỉ khóa KHÁCH HÀNG, atomic + thu hồi token. */
function actingAsUserAdmin(): void
{
    Sanctum::actingAs(User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]));
}

it('khóa tài khoản khách hàng + thu hồi toàn bộ token', function () {
    actingAsUserAdmin();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer, 'is_active' => true]);
    $customer->createToken('customer_token');

    $this->postJson("/api/admin/users/{$customer->id}/ban", ['reason' => 'Vi phạm điều khoản'])
        ->assertOk();

    expect($customer->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $customer->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'ban_user', 'model_id' => $customer->id]);
});

it('chặn khóa tài khoản KHÔNG phải khách hàng (nhà xe/tài xế/admin)', function () {
    actingAsUserAdmin();
    $operator = User::factory()->create(['role' => UserRoleEnum::Operator, 'is_active' => true]);

    $this->postJson("/api/admin/users/{$operator->id}/ban", ['reason' => 'x'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'USER_NOT_CUSTOMER');

    expect($operator->fresh()->is_active)->toBeTrue();
});

it('chặn khóa tài khoản đã bị khóa (idempotent)', function () {
    actingAsUserAdmin();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer, 'is_active' => false]);

    $this->postJson("/api/admin/users/{$customer->id}/ban", ['reason' => 'x'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'USER_ALREADY_BANNED');
});

it('yêu cầu nhập lý do khi khóa', function () {
    actingAsUserAdmin();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer, 'is_active' => true]);

    $this->postJson("/api/admin/users/{$customer->id}/ban", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

it('mở khóa khách hàng đã bị khóa', function () {
    actingAsUserAdmin();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer, 'is_active' => false]);

    $this->postJson("/api/admin/users/{$customer->id}/unban")->assertOk();

    expect($customer->fresh()->is_active)->toBeTrue();
});

it('chặn mở khóa tài khoản đang hoạt động', function () {
    actingAsUserAdmin();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer, 'is_active' => true]);

    $this->postJson("/api/admin/users/{$customer->id}/unban")
        ->assertStatus(422)
        ->assertJsonPath('code', 'USER_ALREADY_ACTIVE');
});

it('trả 404 code khi user không tồn tại', function () {
    actingAsUserAdmin();

    $this->postJson('/api/admin/users/'.Str::uuid().'/ban', ['reason' => 'x'])
        ->assertStatus(404)
        ->assertJsonPath('code', 'USER_NOT_FOUND');
});
