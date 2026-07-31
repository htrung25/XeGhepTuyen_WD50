<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/**
 * Cách B: middleware `role:<portal>` chặn token Sanctum của role khác truy cập
 * nhóm route đã xác thực của một portal khác (cùng model User → auth:sanctum
 * không tự cô lập được).
 */
function actingAsRole(UserRoleEnum $role): User
{
    $attrs = ['role' => $role];
    if ($role === UserRoleEnum::Admin) {
        $attrs['admin_role_id'] = superAdminRole()->id;
    }
    $user = User::factory()->create($attrs);
    Sanctum::actingAs($user);

    return $user;
}

it('cho phép admin truy cập route admin đã xác thực', function () {
    actingAsRole(UserRoleEnum::Admin);

    $this->getJson('/api/admin/dashboard')->assertOk();
});

it('chặn token customer truy cập route admin', function () {
    actingAsRole(UserRoleEnum::Customer);

    $this->getJson('/api/admin/dashboard')->assertForbidden();
});

it('chặn token driver truy cập route admin', function () {
    actingAsRole(UserRoleEnum::Driver);

    $this->getJson('/api/admin/dashboard')->assertForbidden();
});

it('chặn token operator truy cập route admin', function () {
    actingAsRole(UserRoleEnum::Operator);

    $this->getJson('/api/admin/dashboard')->assertForbidden();
});

it('chặn token admin truy cập route operator', function () {
    actingAsRole(UserRoleEnum::Admin);

    $this->getJson('/api/operator/onboarding/fleet')->assertForbidden();
});

it('chặn token customer truy cập route driver', function () {
    actingAsRole(UserRoleEnum::Customer);

    $this->getJson('/api/driver/trips')->assertForbidden();
});

it('vẫn từ chối khi không có token (401)', function () {
    $this->getJson('/api/admin/dashboard')->assertUnauthorized();
});

it('trả JSON 401 khi API request không gửi Accept application/json', function () {
    $this->get('/api/admin/dashboard')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});

it('chặn đăng nhập admin đã bị khóa', function () {
    $admin = User::factory()->admin()->create([
        'admin_role_id' => superAdminRole()->id,
        'email' => 'locked-admin@example.com',
        'password' => Hash::make('password'),
        'is_active' => false,
    ]);

    $this->postJson('/api/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertForbidden();
});

it('chặn đăng nhập driver đã bị khóa', function () {
    $operator = makeOperatorWithRevenue(0, 0);
    $user = $operator->drivers()->firstOrFail()->user;
    $user->update([
        'phone' => '0900000011',
        'password' => Hash::make('password'),
        'is_active' => false,
    ]);

    $this->postJson('/api/driver/auth/login', [
        'phone' => $user->phone,
        'password' => 'password',
    ])->assertForbidden();
});

it('chặn đăng nhập operator đã bị khóa', function () {
    $operator = makeOperatorWithRevenue(0, 0);
    $operator->user->update([
        'password' => Hash::make('password'),
        'is_active' => false,
    ]);

    $this->postJson('/api/operator/auth/login', [
        'phone' => $operator->user->phone,
        'password' => 'password',
    ])->assertForbidden();
});

it('chặn token của customer đã bị khóa', function () {
    $user = actingAsRole(UserRoleEnum::Customer);
    $user->update(['is_active' => false]);

    $this->getJson('/api/customer/notifications')->assertForbidden();
});

it('chặn token của driver đã bị khóa', function () {
    $user = actingAsRole(UserRoleEnum::Driver);
    $user->update(['is_active' => false]);

    $this->getJson('/api/driver/notifications')->assertForbidden();
});

it('chặn token của operator đã bị khóa', function () {
    $user = actingAsRole(UserRoleEnum::Operator);
    $user->update(['is_active' => false]);

    $this->getJson('/api/operator/bookings')->assertForbidden();
});

it('chặn token của admin đã bị khóa', function () {
    $user = actingAsRole(UserRoleEnum::Admin);
    $user->update(['is_active' => false]);

    $this->getJson('/api/admin/dashboard')->assertForbidden();
});
