<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Operator;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function makeAuditLogAdminUser(): User
{
    return User::factory()->create([
        'role' => UserRole::Admin,
    ]);
}

it('ghi audit log khi admin khoa tai khoan nguoi dung', function () {
    $admin = makeAuditLogAdminUser();
    Sanctum::actingAs($admin);

    $user = User::factory()->create(['is_active' => true]);

    $this->postJson("/api/admin/users/{$user->id}/ban", [
        'reason' => 'Vi pham dieu khoan',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'ban_user',
        'model_type' => User::class,
        'model_id' => $user->id,
    ]);
});

it('cho phep admin lay danh sach audit logs va ho tro loc va tim kiem', function () {
    $admin = makeAuditLogAdminUser();
    Sanctum::actingAs($admin);

    // Tao mot so log
    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'ban_user',
        'model_type' => User::class,
        'model_id' => User::factory()->create()->id,
        'description' => 'Khoa tai khoan Nguyen Van A',
    ]);

    AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'approve_operator',
        'model_type' => Operator::class,
        'model_id' => User::factory()->create()->id,
        'description' => 'Duyet nha xe Hoa Mai',
    ]);

    // Test lay danh sach tat ca
    $response = $this->getJson('/api/admin/audit-logs')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // Test filter theo action
    $this->getJson('/api/admin/audit-logs?action=ban_user')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.action', 'ban_user');

    // Test search theo description
    $this->getJson('/api/admin/audit-logs?search=Hoa Mai')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.description', 'Duyet nha xe Hoa Mai');
});

it('cho phep admin xem chi tiet mot audit log', function () {
    $admin = makeAuditLogAdminUser();
    Sanctum::actingAs($admin);

    $log = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'ban_user',
        'model_type' => User::class,
        'model_id' => User::factory()->create()->id,
        'description' => 'Khoa tai khoan Nguyen Van A',
        'old_values' => ['is_active' => true],
        'new_values' => ['is_active' => false],
    ]);

    $this->getJson("/api/admin/audit-logs/{$log->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $log->id)
        ->assertJsonPath('data.description', 'Khoa tai khoan Nguyen Van A')
        ->assertJsonPath('data.old_values.is_active', true)
        ->assertJsonPath('data.new_values.is_active', false);
});

it('chan nguoi dung khong phai admin truy cap audit logs', function () {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);
    Sanctum::actingAs($customer);

    $this->getJson('/api/admin/audit-logs')
        ->assertStatus(403);
});

it('tra ve 404 khi xem audit log khong ton tai', function () {
    Sanctum::actingAs(makeAuditLogAdminUser());

    $this->getJson('/api/admin/audit-logs/'.\Illuminate\Support\Str::uuid())
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

it('loc audit logs theo khoang ngay', function () {
    $admin = makeAuditLogAdminUser();
    Sanctum::actingAs($admin);

    $old = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'ban_user',
        'description' => 'Log cu',
    ]);
    $old->forceFill(['created_at' => '2026-01-10 10:00:00'])->save();

    $recent = AuditLog::create([
        'user_id' => $admin->id,
        'action' => 'ban_user',
        'description' => 'Log moi',
    ]);
    $recent->forceFill(['created_at' => '2026-06-20 10:00:00'])->save();

    // Chi lay log trong khoang -> chi 1 ban ghi "moi"
    $this->getJson('/api/admin/audit-logs?date_from=2026-06-01&date_to=2026-06-30')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.description', 'Log moi');
});
