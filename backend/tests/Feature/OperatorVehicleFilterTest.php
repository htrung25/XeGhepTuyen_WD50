<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use App\Models\Vehicle;
use Laravel\Sanctum\Sanctum;

function vehicleFilterFixture(): array
{
    $user = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $user->id,
        'company_name' => 'Nhà xe lọc xe',
        'business_license' => 'GPKD-FILTER-1',
        'status' => 'verified',
    ]);

    $assigned = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29A-12345',
        'brand' => 'Toyota',
        'model' => 'Vios',
        'color' => 'Trắng',
        'vehicle_type' => 'sedan_4',
        'seat_count' => 4,
        'status' => 'active',
    ]);
    $unassigned = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '30B-67890',
        'brand' => 'Ford',
        'model' => 'Transit',
        'color' => 'Đen',
        'vehicle_type' => 'minibus_16',
        'seat_count' => 16,
        'status' => 'inactive',
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id,
        'operator_id' => $operator->id,
        'license_number' => 'GPLX-FILTER-1',
        'license_class' => 'B2',
        'license_expiry' => now()->addYears(3),
        'id_card_number' => 'CCCD-FILTER-1',
        'status' => 'verified',
        'current_vehicle_id' => $assigned->id,
    ]);

    return [$user, $assigned, $unassigned, $driver];
}

beforeEach(function (): void {
    $this->filterFixture = vehicleFilterFixture();
    [$user] = $this->filterFixture;
    Sanctum::actingAs($user, ['*'], 'sanctum');
    Sanctum::actingAs($user, ['*'], 'operator');
});

it('lọc danh sách xe theo tìm kiếm, loại, trạng thái và phân công', function (): void {
    [, $assigned, $unassigned] = $this->filterFixture;

    $this->getJson('/api/operator/vehicles?search=toyota')
        ->assertOk()
        ->assertJsonPath('data.0.id', $assigned->id);

    $this->getJson('/api/operator/vehicles?vehicle_type=minibus_16&status=inactive&assignment=unassigned')
        ->assertOk()
        ->assertJsonPath('data.0.id', $unassigned->id);
});

it('không trả về xe của nhà xe khác khi lọc danh sách', function (): void {
    $otherUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $otherOperator = Operator::create([
        'user_id' => $otherUser->id,
        'company_name' => 'Nhà xe khác',
        'business_license' => 'GPKD-FILTER-2',
        'status' => 'verified',
    ]);
    Vehicle::create([
        'operator_id' => $otherOperator->id,
        'plate_number' => '99A-99999',
        'brand' => 'Toyota',
        'model' => 'Vios',
        'vehicle_type' => 'sedan_4',
        'seat_count' => 4,
        'status' => 'active',
    ]);

    $this->getJson('/api/operator/vehicles?search=toyota')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
