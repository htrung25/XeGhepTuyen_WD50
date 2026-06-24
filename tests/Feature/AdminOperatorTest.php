<?php

use App\Enums\UserRole;
use App\Models\Operator;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use Laravel\Sanctum\Sanctum;

function makeAdminUser(): User
{
    return User::factory()->create([
        'role' => UserRole::Admin,
    ]);
}

function makeFullOperator(): Operator
{
    $user = User::factory()->create([
        'role' => UserRole::Operator,
    ]);

    $operator = Operator::create([
        'user_id' => $user->id,
        'company_name' => 'Nhà xe Sao Việt',
        'business_license' => 'GPKD-99999',
        'tax_code' => '0109999999',
        'bank_name' => 'Techcombank',
        'bank_account' => '19033333333333',
        'bank_account_name' => 'CONG TY SAO VIET',
        'logo_url' => 'https://example.com/logo.png',
        'description' => 'Nhà xe chất lượng cao chuyên tuyến Hà Nội Lào Cai',
        'status' => 'verified',
        'commission_rate' => 10.00,
    ]);

    // Create a vehicle
    Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29A-999.99',
        'brand' => 'Ford',
        'model' => 'Transit',
        'color' => 'Bạc',
        'year' => 2022,
        'vehicle_type' => 'mpv_7',
        'seat_count' => 7,
        'status' => 'active',
    ]);

    // Create a driver
    $driverUser = User::factory()->create([
        'role' => UserRole::Driver,
    ]);

    Driver::create([
        'operator_id' => $operator->id,
        'user_id' => $driverUser->id,
        'license_number' => '123456789012',
        'license_class' => 'D',
        'license_expiry' => '2030-01-01',
        'id_card_number' => '123456789012',
        'status' => 'verified',
    ]);

    return $operator;
}

it('cho phép admin lấy danh sách nhà xe', function () {
    Sanctum::actingAs(makeAdminUser());
    $operator = makeFullOperator();

    $response = $this->getJson('/api/admin/operators')
        ->assertOk()
        ->assertJsonPath('data.0.id', $operator->id)
        ->assertJsonPath('data.0.company_name', 'Nhà xe Sao Việt');
});

it('cho phép admin xem chi tiết thông tin nhà xe với đầy đủ thông tin', function () {
    Sanctum::actingAs(makeAdminUser());
    $operator = makeFullOperator();

    $response = $this->getJson('/api/admin/operators/' . $operator->id)
        ->assertOk()
        ->assertJsonPath('data.id', $operator->id)
        ->assertJsonPath('data.company_name', 'Nhà xe Sao Việt')
        ->assertJsonPath('data.tax_code', '0109999999')
        ->assertJsonPath('data.business_license', 'GPKD-99999')
        ->assertJsonPath('data.bank_name', 'Techcombank')
        ->assertJsonPath('data.bank_account', '19033333333333')
        ->assertJsonPath('data.bank_account_name', 'CONG TY SAO VIET')
        ->assertJsonPath('data.logo_url', 'https://example.com/logo.png')
        ->assertJsonPath('data.description', 'Nhà xe chất lượng cao chuyên tuyến Hà Nội Lào Cai')
        // Check loaded relationships
        ->assertJsonCount(1, 'data.vehicles')
        ->assertJsonPath('data.vehicles.0.plate_number', '29A-999.99')
        ->assertJsonCount(1, 'data.drivers')
        ->assertJsonPath('data.drivers.0.phone', $operator->drivers->first()->user->phone);
});

it('chặn người dùng không có vai trò admin truy cập chi tiết nhà xe', function () {
    $operator = makeFullOperator();
    
    // Act as a normal customer
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);
    Sanctum::actingAs($customer);

    $this->getJson('/api/admin/operators/' . $operator->id)
        ->assertForbidden();
});

it('trả về 404 khi xem chi tiết nhà xe không tồn tại', function () {
    Sanctum::actingAs(makeAdminUser());

    $this->getJson('/api/admin/operators/non-existent-uuid')
        ->assertNotFound();
});

it('cho phép admin đình chỉ nhà xe', function () {
    Sanctum::actingAs(makeAdminUser());
    $operator = makeFullOperator();
    $operator->update(['status' => 'verified']);

    $response = $this->postJson("/api/admin/operators/{$operator->id}/suspend", [
        'reason' => 'Vi phạm điều khoản dịch vụ',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $operator->refresh();
    expect($operator->status->value)->toBe('suspended');
    expect($operator->user->is_active)->toBeFalse();
});

it('cho phép admin khôi phục nhà xe bị đình chỉ', function () {
    Sanctum::actingAs(makeAdminUser());
    $operator = makeFullOperator();
    $operator->update(['status' => 'suspended']);
    $operator->user->update(['is_active' => false]);

    $response = $this->postJson("/api/admin/operators/{$operator->id}/restore")
        ->assertOk()
        ->assertJsonPath('success', true);

    $operator->refresh();
    expect($operator->status->value)->toBe('verified');
    expect($operator->user->is_active)->toBeTrue();
});

it('chặn người dùng không có vai trò admin khôi phục nhà xe', function () {
    $operator = makeFullOperator();
    $operator->update(['status' => 'suspended']);

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);
    Sanctum::actingAs($customer);

    $this->postJson("/api/admin/operators/{$operator->id}/restore")
        ->assertForbidden();
});
