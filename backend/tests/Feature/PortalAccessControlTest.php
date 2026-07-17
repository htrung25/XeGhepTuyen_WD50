<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
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
