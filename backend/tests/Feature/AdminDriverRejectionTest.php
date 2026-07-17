<?php

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

function makeDriverForAdminRejection(DriverStatus $status = DriverStatus::Pending): Driver
{
    $operatorUser = User::factory()->create(['role' => UserRole::Operator]);
    $operator = Operator::create([
        'user_id' => $operatorUser->id,
        'company_name' => 'Nhà xe kiểm thử từ chối',
        'business_license' => 'GP-'.fake()->unique()->numerify('######'),
        'status' => 'verified',
    ]);

    return Driver::create([
        'user_id' => User::factory()->create(['role' => UserRole::Driver])->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'),
        'license_class' => 'B2',
        'license_expiry' => now()->addYears(3),
        'id_card_number' => fake()->unique()->numerify('############'),
        'status' => $status,
    ]);
}

beforeEach(function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'admin_role_id' => superAdminRole()->id,
    ]);

    Sanctum::actingAs($admin);
    auth()->guard('admin')->setUser($admin);
});

it('lưu lý do khi admin từ chối tài xế đang chờ duyệt', function () {
    $driver = makeDriverForAdminRejection();
    $reason = 'Ảnh giấy phép lái xe không rõ thông tin.';

    $this->postJson("/api/admin/drivers/{$driver->id}/reject", [
        'reason' => $reason,
    ])->assertOk()
        ->assertJsonPath('success', true);

    $driver->refresh();

    expect($driver->status)->toBe(DriverStatus::Rejected)
        ->and($driver->reject_reason)->toBe($reason);
});

it('trả lý do từ chối trong danh sách được lọc theo trạng thái rejected', function () {
    $rejected = makeDriverForAdminRejection(DriverStatus::Rejected);
    $rejected->update(['reject_reason' => 'CCCD không hợp lệ.']);
    makeDriverForAdminRejection(DriverStatus::Pending);

    $this->getJson('/api/admin/drivers?status=rejected')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $rejected->id)
        ->assertJsonPath('data.0.status', DriverStatus::Rejected->value)
        ->assertJsonPath('data.0.reject_reason', 'CCCD không hợp lệ.');
});

it('không cho từ chối lại tài xế không còn ở trạng thái chờ duyệt', function (DriverStatus $status) {
    $driver = makeDriverForAdminRejection($status);

    $this->postJson("/api/admin/drivers/{$driver->id}/reject", [
        'reason' => 'Thử thay đổi trạng thái không hợp lệ.',
    ])->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect($driver->refresh()->status)->toBe($status)
        ->and($driver->reject_reason)->toBeNull();
})->with([
    'đã duyệt' => DriverStatus::Verified,
    'đình chỉ' => DriverStatus::Suspended,
    'đã từ chối' => DriverStatus::Rejected,
]);

it('không cho reject ghi đè kết quả approve', function () {
    Queue::fake([SendSmsNotificationJob::class]);
    $driver = makeDriverForAdminRejection();

    $this->postJson("/api/admin/drivers/{$driver->id}/approve")->assertOk();
    $this->postJson("/api/admin/drivers/{$driver->id}/reject", [
        'reason' => 'Request đến sau approve.',
    ])->assertUnprocessable();

    $driver->refresh();
    expect($driver->status)->toBe(DriverStatus::Verified)
        ->and($driver->reject_reason)->toBeNull()
        ->and($driver->user->is_active)->toBeTrue();
});

it('không cho approve ghi đè kết quả reject', function () {
    Queue::fake([SendSmsNotificationJob::class]);
    $driver = makeDriverForAdminRejection();

    $this->postJson("/api/admin/drivers/{$driver->id}/reject", [
        'reason' => 'Hồ sơ không hợp lệ.',
    ])->assertOk();
    $this->postJson("/api/admin/drivers/{$driver->id}/approve")->assertUnprocessable();

    $driver->refresh();
    expect($driver->status)->toBe(DriverStatus::Rejected)
        ->and($driver->reject_reason)->toBe('Hồ sơ không hợp lệ.');

    Queue::assertNotPushed(SendSmsNotificationJob::class);
});
