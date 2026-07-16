<?php

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/** Tạo admin gắn vai trò có tập quyền cho trước rồi acting. */
function permTestAdmin(array $permissions, bool $super = false): User
{
    $role = AdminRole::create([
        'name' => 'Vai trò test '.Str::random(5),
        'slug' => 'perm-'.Str::random(8),
        'permissions' => $permissions,
        'is_super' => $super,
        'is_system' => false,
    ]);

    $admin = User::factory()->admin()->create(['admin_role_id' => $role->id]);
    Sanctum::actingAs($admin);

    return $admin;
}

it('cho phep admin co quyen dashboard.view xem dashboard', function () {
    permTestAdmin(['dashboard.view']);
    $this->getJson('/api/admin/dashboard')->assertOk();
});

it('chan admin thieu quyen dashboard.view', function () {
    permTestAdmin(['users.view']);
    $this->getJson('/api/admin/dashboard')->assertForbidden();
});

it('super admin truy cap moi endpoint du khong liet ke quyen', function () {
    permTestAdmin([], super: true);
    $this->getJson('/api/admin/dashboard')->assertOk();
    $this->getJson('/api/admin/operators')->assertOk();
    $this->getJson('/api/admin/finance/summary')->assertOk();
    $this->getJson('/api/admin/roles')->assertOk();
});

it('admin khong co vai tro bi chan endpoint can quyen', function () {
    $admin = User::factory()->admin()->create(['admin_role_id' => null]);
    Sanctum::actingAs($admin);
    $this->getJson('/api/admin/dashboard')->assertForbidden();
});

it('quyen view khac quyen hanh dong: co view nhung khong duyet nha xe', function () {
    permTestAdmin(['operators.view']);
    $this->getJson('/api/admin/operators')->assertOk();
    // thiếu operators.review → middleware chặn trước khi vào controller
    $this->postJson('/api/admin/operators/'.Str::uuid().'/approve')->assertForbidden();
});

it('co quyen operators.review thi qua middleware (404 vi nha xe khong ton tai)', function () {
    permTestAdmin(['operators.review']);
    $this->postJson('/api/admin/operators/'.Str::uuid().'/approve')->assertStatus(404);
});

it('doi quyen cua vai tro thay doi truy cap', function () {
    $role = AdminRole::create([
        'name' => 'Vai trò động',
        'slug' => 'dyn-'.Str::random(8),
        'permissions' => ['users.view'],
        'is_super' => false,
        'is_system' => false,
    ]);
    $admin = User::factory()->admin()->create(['admin_role_id' => $role->id]);

    Sanctum::actingAs($admin->fresh());
    $this->getJson('/api/admin/finance/summary')->assertForbidden();

    $role->update(['permissions' => ['finance.view']]);

    Sanctum::actingAs($admin->fresh());
    $this->getJson('/api/admin/finance/summary')->assertOk();
});

it('login tra ve permissions va is_super', function () {
    $role = AdminRole::create([
        'name' => 'Super',
        'slug' => 'super-'.Str::random(8),
        'permissions' => [],
        'is_super' => true,
        'is_system' => true,
    ]);
    $admin = User::factory()->admin()->create([
        'email' => 'sa-'.Str::random(5).'@xeghep.vn',
        'admin_role_id' => $role->id,
        'password' => 'Secret@123',
    ]);

    $this->postJson('/api/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'Secret@123',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.is_super', true)
        ->assertJsonPath('data.user.role', 'admin');
});
