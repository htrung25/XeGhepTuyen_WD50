<?php

use App\Enums\UserRole;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Hồ sơ nhà xe (operator): vá bug me() trả sai tên cột + thêm cập nhật hồ sơ
 * (gồm tài khoản ngân hàng nhận tiền — F-O01) và đổi mật khẩu.
 */
function makeOperatorUser(array $operatorAttrs = []): User
{
    $user = User::factory()->create([
        'role' => UserRole::Operator,
        'password' => Hash::make('old-password'),
    ]);

    Operator::create(array_merge([
        'user_id' => $user->id,
        'company_name' => 'Nhà xe Bắc Hà',
        'business_license' => 'GPKD-12345',
        'tax_code' => '0101234567',
        'status' => 'verified',
    ], $operatorAttrs));

    return $user;
}

it('me trả đúng tax_code và business_license (regression bug tên cột)', function () {
    Sanctum::actingAs(makeOperatorUser());

    $this->getJson('/api/operator/auth/me')
        ->assertOk()
        ->assertJsonPath('data.operator.tax_code', '0101234567')
        ->assertJsonPath('data.operator.business_license', 'GPKD-12345');
});

it('cập nhật hồ sơ + tài khoản ngân hàng nhận tiền', function () {
    $user = makeOperatorUser();
    Sanctum::actingAs($user);

    $this->putJson('/api/operator/auth/profile', [
        'company_name' => 'Nhà xe Mới',
        'bank_account' => '0123456789',
        'bank_name' => 'Vietcombank',
        'bank_account_name' => 'NHA XE MOI',
    ])->assertOk();

    $operator = $user->fresh()->operator;
    expect($operator->company_name)->toBe('Nhà xe Mới');
    expect($operator->bank_account)->toBe('0123456789');
    expect($operator->bank_name)->toBe('Vietcombank');
});

it('không cho sửa business_license/tax_code qua updateProfile', function () {
    $user = makeOperatorUser();
    Sanctum::actingAs($user);

    $this->putJson('/api/operator/auth/profile', [
        'company_name' => 'X',
        'business_license' => 'HACKED',
        'tax_code' => 'HACKED',
    ])->assertOk();

    $operator = $user->fresh()->operator;
    expect($operator->business_license)->toBe('GPKD-12345');
    expect($operator->tax_code)->toBe('0101234567');
});

it('tải logo nhà xe', function () {
    Storage::fake('public');
    $user = makeOperatorUser();
    Sanctum::actingAs($user);

    $this->putJson('/api/operator/auth/profile', [
        'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertOk();

    expect($user->fresh()->operator->logo_url)->not->toBeNull();
});

it('đổi mật khẩu thành công khi mật khẩu cũ đúng', function () {
    Sanctum::actingAs(makeOperatorUser());

    $this->putJson('/api/operator/auth/password', [
        'old_password' => 'old-password',
        'new_password' => 'new-password-123',
        'new_password_confirmation' => 'new-password-123',
    ])->assertOk();
});

it('từ chối đổi mật khẩu khi mật khẩu cũ sai (422)', function () {
    Sanctum::actingAs(makeOperatorUser());

    $this->putJson('/api/operator/auth/password', [
        'old_password' => 'wrong',
        'new_password' => 'new-password-123',
        'new_password_confirmation' => 'new-password-123',
    ])->assertStatus(422)->assertJsonPath('code', 'INVALID_CURRENT_PASSWORD');
});
