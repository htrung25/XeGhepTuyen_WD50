<?php

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function roleTestSuperAdmin(): User
{
    $role = AdminRole::create([
        'name' => 'Super',
        'slug' => 'super-'.Str::random(8),
        'permissions' => [],
        'is_super' => true,
        'is_system' => true,
    ]);
    $admin = User::factory()->admin()->create(['admin_role_id' => $role->id]);
    Sanctum::actingAs($admin);

    return $admin;
}

it('tao vai tro moi va ghi audit', function () {
    roleTestSuperAdmin();

    $this->postJson('/api/admin/roles', [
        'name' => 'Kế toán',
        'permissions' => ['finance.view', 'finance.payout'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Kế toán')
        ->assertJsonPath('data.is_system', false);

    $this->assertDatabaseHas('admin_roles', ['name' => 'Kế toán', 'slug' => 'ke-toan']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'create_role']);
});

it('chan tao vai tro voi quyen khong thuoc catalog', function () {
    roleTestSuperAdmin();

    $this->postJson('/api/admin/roles', [
        'name' => 'Lỗi',
        'permissions' => ['finance.hack'],
    ])->assertStatus(422);
});

it('chan sua quyen vai tro he thong', function () {
    roleTestSuperAdmin();
    $sys = AdminRole::create([
        'name' => 'Hệ thống', 'slug' => 'sys-'.Str::random(6),
        'permissions' => ['dashboard.view'], 'is_super' => false, 'is_system' => true,
    ]);

    $this->putJson("/api/admin/roles/{$sys->id}", ['permissions' => ['finance.view']])
        ->assertStatus(422);
});

it('chan xoa vai tro he thong', function () {
    roleTestSuperAdmin();
    $sys = AdminRole::create([
        'name' => 'Hệ thống', 'slug' => 'sys-'.Str::random(6),
        'permissions' => [], 'is_super' => false, 'is_system' => true,
    ]);

    $this->deleteJson("/api/admin/roles/{$sys->id}")->assertStatus(422);
});

it('chan xoa vai tro dang duoc gan cho nhan vien', function () {
    roleTestSuperAdmin();
    $role = AdminRole::create([
        'name' => 'Đang dùng', 'slug' => 'used-'.Str::random(6),
        'permissions' => ['dashboard.view'], 'is_super' => false, 'is_system' => false,
    ]);
    User::factory()->admin()->create(['admin_role_id' => $role->id]);

    $this->deleteJson("/api/admin/roles/{$role->id}")->assertStatus(422);
});

it('xoa vai tro khong gan thanh cong', function () {
    roleTestSuperAdmin();
    $role = AdminRole::create([
        'name' => 'Rảnh', 'slug' => 'free-'.Str::random(6),
        'permissions' => ['dashboard.view'], 'is_super' => false, 'is_system' => false,
    ]);

    $this->deleteJson("/api/admin/roles/{$role->id}")->assertOk();
    $this->assertDatabaseMissing('admin_roles', ['id' => $role->id]);
});

it('tra ve danh muc quyen gom theo module', function () {
    roleTestSuperAdmin();

    $this->getJson('/api/admin/roles/permissions')
        ->assertOk()
        ->assertJsonPath('data.0.module', 'Tổng quan');
});

it('admin thieu quyen admin_roles.manage khong tao duoc vai tro', function () {
    $role = AdminRole::create([
        'name' => 'Chỉ xem vai trò', 'slug' => 'ro-'.Str::random(6),
        'permissions' => ['admin_roles.view'], 'is_super' => false, 'is_system' => false,
    ]);
    Sanctum::actingAs(User::factory()->admin()->create(['admin_role_id' => $role->id]));

    $this->postJson('/api/admin/roles', ['name' => 'X', 'permissions' => []])
        ->assertForbidden();
});
