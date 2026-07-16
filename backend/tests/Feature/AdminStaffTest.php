<?php

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function staffTestSuperRole(): AdminRole
{
    return AdminRole::create([
        'name' => 'Super', 'slug' => 'super-'.Str::random(8),
        'permissions' => [], 'is_super' => true, 'is_system' => true,
    ]);
}

function staffTestSuperAdmin(): User
{
    $admin = User::factory()->admin()->create(['admin_role_id' => staffTestSuperRole()->id]);
    Sanctum::actingAs($admin);

    return $admin;
}

it('tao nhan vien admin va dang nhap duoc bang mat khau tam', function () {
    staffTestSuperAdmin();
    $role = AdminRole::create([
        'name' => 'Kế toán', 'slug' => 'kt-'.Str::random(6),
        'permissions' => ['finance.view'], 'is_super' => false, 'is_system' => false,
    ]);

    $res = $this->postJson('/api/admin/admin-staff', [
        'full_name' => 'Nhân Viên A',
        'email' => 'nva@xeghep.vn',
        'phone' => '0911111111',
        'admin_role_id' => $role->id,
    ])->assertCreated();

    $temp = $res->json('data.temp_password');
    expect($temp)->not->toBeNull();

    $this->assertDatabaseHas('users', ['email' => 'nva@xeghep.vn', 'role' => 'admin', 'admin_role_id' => $role->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'create_admin']);

    $this->postJson('/api/admin/auth/login', ['email' => 'nva@xeghep.vn', 'password' => $temp])
        ->assertOk();
});

it('chan tu khoa tai khoan cua chinh minh', function () {
    $me = staffTestSuperAdmin();
    $this->postJson("/api/admin/admin-staff/{$me->id}/ban")->assertStatus(422);
});

it('chan khoa super admin cuoi cung', function () {
    $superRole = staffTestSuperRole();
    $onlySuper = User::factory()->admin()->create(['admin_role_id' => $superRole->id]);

    // Người thao tác là admin có quyền quản lý nhân viên nhưng KHÔNG phải super
    $mgrRole = AdminRole::create([
        'name' => 'Quản lý NV', 'slug' => 'mgr-'.Str::random(6),
        'permissions' => ['admin_staff.view', 'admin_staff.manage'], 'is_super' => false, 'is_system' => false,
    ]);
    Sanctum::actingAs(User::factory()->admin()->create(['admin_role_id' => $mgrRole->id]));

    $this->postJson("/api/admin/admin-staff/{$onlySuper->id}/ban")->assertStatus(422);
});

it('dat lai mat khau nhan vien', function () {
    staffTestSuperAdmin();
    $role = AdminRole::create([
        'name' => 'NV', 'slug' => 'nv-'.Str::random(6),
        'permissions' => ['dashboard.view'], 'is_super' => false, 'is_system' => false,
    ]);
    $staff = User::factory()->admin()->create(['admin_role_id' => $role->id]);

    $res = $this->postJson("/api/admin/admin-staff/{$staff->id}/reset-password")->assertOk();
    $temp = $res->json('data.temp_password');

    $this->postJson('/api/admin/auth/login', ['email' => $staff->email, 'password' => $temp])
        ->assertOk();
});

it('doi vai tro nhan vien', function () {
    staffTestSuperAdmin();
    $roleA = AdminRole::create([
        'name' => 'A', 'slug' => 'a-'.Str::random(6),
        'permissions' => ['users.view'], 'is_super' => false, 'is_system' => false,
    ]);
    $roleB = AdminRole::create([
        'name' => 'B', 'slug' => 'b-'.Str::random(6),
        'permissions' => ['finance.view'], 'is_super' => false, 'is_system' => false,
    ]);
    $staff = User::factory()->admin()->create(['admin_role_id' => $roleA->id]);

    $this->putJson("/api/admin/admin-staff/{$staff->id}", ['admin_role_id' => $roleB->id])
        ->assertOk();

    $this->assertDatabaseHas('users', ['id' => $staff->id, 'admin_role_id' => $roleB->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'update_admin']);
});
