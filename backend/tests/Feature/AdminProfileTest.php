<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function makeAdminUserForProfile(): User
{
    return User::factory()->create([
        'role' => UserRole::Admin,
    ]);
}

it('cho phép admin lấy thông tin cá nhân của mình', function () {
    $admin = makeAdminUserForProfile();
    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/admin/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', $admin->email)
        ->assertJsonPath('data.full_name', $admin->full_name);
});

it('cho phép admin cập nhật thông tin cá nhân', function () {
    $admin = makeAdminUserForProfile();
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/admin/auth/profile', [
        'full_name' => 'Admin Đẹp Trai',
        'email' => 'newadmin@xeghep.com',
        'phone' => '0987654321',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.full_name', 'Admin Đẹp Trai')
        ->assertJsonPath('data.email', 'newadmin@xeghep.com')
        ->assertJsonPath('data.phone', '0987654321');

    $admin->refresh();
    expect($admin->full_name)->toBe('Admin Đẹp Trai');
    expect($admin->email)->toBe('newadmin@xeghep.com');
    expect($admin->phone)->toBe('0987654321');
});

it('cho phép admin đổi mật khẩu', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'password' => Hash::make('password123'),
    ]);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/admin/auth/change-password', [
        'old_password' => 'password123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $admin->refresh();
    expect(Hash::check('newpassword123', $admin->password))->toBeTrue();
});

it('chặn đổi mật khẩu khi nhập sai mật khẩu cũ', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'password' => Hash::make('password123'),
    ]);
    Sanctum::actingAs($admin);

    $response = $this->putJson('/api/admin/auth/change-password', [
        'old_password' => 'wrongpassword',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'newpassword123',
    ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});
