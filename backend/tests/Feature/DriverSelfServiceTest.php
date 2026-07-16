<?php

use App\Enums\UserRole;
use App\Models\Driver;
use App\Models\Notification;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Endpoint tự phục vụ của tài xế (#2 + #3): hồ sơ, đổi mật khẩu, tải giấy tờ,
 * thông báo — vốn trước đây FE gọi nhưng BE chưa có route.
 */
function makeDriverUser(array $userAttrs = []): User
{
    $user = User::factory()->create(array_merge([
        'role' => UserRole::Driver,
        'password' => Hash::make('old-password'),
    ], $userAttrs));

    $operator = Operator::create([
        'user_id' => User::factory()->create(['role' => UserRole::Operator])->id,
        'company_name' => 'Nhà xe Test',
        'business_license' => 'GPKD-001',
    ]);

    Driver::create([
        'user_id' => $user->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'),
        'license_class' => 'B2',
        'license_expiry' => now()->addYears(3),
        'id_card_number' => fake()->numerify('############'),
        'status' => 'verified',
    ]);

    return $user;
}

it('cập nhật hồ sơ tài xế', function () {
    $user = makeDriverUser();
    Sanctum::actingAs($user);

    $this->putJson('/api/driver/auth/profile', [
        'full_name' => 'Tài Xế Mới',
        'birth_date' => '1995-10-15',
    ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Tài Xế Mới')
        ->assertJsonPath('data.birth_date', '1995-10-15');

    expect($user->fresh()->full_name)->toBe('Tài Xế Mới');
    expect($user->fresh()->birth_date->format('Y-m-d'))->toBe('1995-10-15');
});

it('đổi mật khẩu thành công khi mật khẩu cũ đúng', function () {
    Sanctum::actingAs(makeDriverUser());

    $this->putJson('/api/driver/auth/password', [
        'old_password' => 'old-password',
        'new_password' => 'new-password-123',
        'new_password_confirmation' => 'new-password-123',
    ])->assertOk();
});

it('từ chối đổi mật khẩu khi mật khẩu cũ sai (422)', function () {
    Sanctum::actingAs(makeDriverUser());

    $this->putJson('/api/driver/auth/password', [
        'old_password' => 'wrong',
        'new_password' => 'new-password-123',
        'new_password_confirmation' => 'new-password-123',
    ])->assertStatus(422)->assertJsonPath('code', 'INVALID_CURRENT_PASSWORD');
});

it('tải giấy tờ và lưu đúng cột theo type', function () {
    Storage::fake('public');
    $user = makeDriverUser();
    Sanctum::actingAs($user);

    $this->postJson('/api/driver/documents', [
        'type' => 'license_front',
        'file' => UploadedFile::fake()->image('gplx.jpg'),
    ])->assertOk();

    expect($user->driver->fresh()->license_front_url)->not->toBeNull();
});

it('từ chối type giấy tờ không hợp lệ (422)', function () {
    Sanctum::actingAs(makeDriverUser());

    $this->postJson('/api/driver/documents', [
        'type' => 'passport',
        'file' => UploadedFile::fake()->image('x.jpg'),
    ])->assertStatus(422);
});

it('liệt kê và đánh dấu đã đọc thông báo của tài xế', function () {
    $user = makeDriverUser();
    Sanctum::actingAs($user);

    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'system',
        'title' => 'Xin chào',
        'body' => 'Nội dung',
        'channel' => 'in_app',
    ]);

    $this->getJson('/api/driver/notifications')
        ->assertOk()
        ->assertJsonPath('unread_count', 1);

    $this->putJson("/api/driver/notifications/{$notification->id}/read")->assertOk();

    expect($notification->fresh()->is_read)->toBeTrue();
});

it('admin xem được bản đồ GPS chuyến đang chạy', function () {
    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin, 'admin_role_id' => superAdminRole()->id]));

    $this->getJson('/api/admin/dashboard/map')
        ->assertOk()
        ->assertJsonPath('success', true);
});
