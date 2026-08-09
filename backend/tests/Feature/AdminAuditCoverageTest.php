<?php

use App\Enums\PartnerApplicationStatusEnum;
use App\Enums\UserRoleEnum;
use App\Jobs\Notification\SendSmsNotificationJob;
use App\Models\PartnerApplication;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

function makePartnerApplicationForAudit(string $suffix): PartnerApplication
{
    return PartnerApplication::create([
        'company_name' => "Nhà xe Audit {$suffix}",
        'tax_code' => "AUDIT-{$suffix}",
        'address' => 'Hà Nội',
        'vehicle_count' => 1,
        'fleet_breakdown' => ['sedan_4' => 1],
        'representative_name' => 'Nguyễn Văn Audit',
        'phone' => '09'.str_pad($suffix, 8, '0'),
        'email' => "audit-{$suffix}@example.com",
        'status' => PartnerApplicationStatusEnum::Pending,
    ]);
}

it('ghi đúng trạng thái cũ khi duyệt đơn đối tác', function () {
    Queue::fake([SendSmsNotificationJob::class]);
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);
    Sanctum::actingAs($admin);
    $application = makePartnerApplicationForAudit('10000001');

    $this->postJson("/api/admin/partner-applications/{$application->id}/approve", [
        'commission_rate' => 10,
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'approve_partner_application',
        'model_id' => $application->id,
        'old_values' => json_encode(['status' => 'pending']),
    ]);
});

it('ghi đúng trạng thái cũ khi từ chối đơn đối tác', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);
    Sanctum::actingAs($admin);
    $application = makePartnerApplicationForAudit('10000002');

    $this->postJson("/api/admin/partner-applications/{$application->id}/reject", [
        'reason' => 'Hồ sơ không hợp lệ',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'reject_partner_application',
        'model_id' => $application->id,
        'old_values' => json_encode(['status' => 'pending']),
    ]);
});

it('ghi audit cho đăng nhập thành công và thất bại', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
        'password' => Hash::make('correct-password'),
    ]);

    $this->postJson('/api/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized();

    $this->postJson('/api/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'correct-password',
    ])->assertOk();

    $this->assertDatabaseHas('audit_logs', ['action' => 'admin_login_failed', 'model_id' => $admin->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'admin_login', 'user_id' => $admin->id]);
});

it('giới hạn đăng nhập admin sai tối đa năm lần mỗi phút', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
        'admin_role_id' => superAdminRole()->id,
        'password' => Hash::make('correct-password'),
    ]);
    $ip = '198.51.100.77';
    Cache::clear();
    $this->withServerVariables(['REMOTE_ADDR' => $ip]);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/admin/auth/login', [
            'email' => $admin->email,
            'password' => "wrong-password-{$attempt}",
        ])->assertUnauthorized();
    }

    $this->postJson('/api/admin/auth/login', [
        'email' => $admin->email,
        'password' => 'wrong-password-6',
    ])->assertTooManyRequests();

    $this->assertDatabaseCount('audit_logs', 5);

    Cache::clear();
});
