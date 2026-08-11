<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\Sanctum;

function vehicleAssignmentFixture(): array
{
    $operatorUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $operatorUser->id,
        'company_name' => 'Nhà xe phân công',
        'business_license' => 'GPKD-ASSIGN-1',
        'status' => 'verified',
    ]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29B-67890',
        'brand' => 'Ford',
        'model' => 'Transit',
        'vehicle_type' => 'minibus_16',
        'seat_count' => 16,
        'status' => 'active',
    ]);
    $makeDriver = function (string $license, string $phone) use ($operator): Driver {
        return Driver::create([
            'user_id' => User::factory()->create([
                'role' => UserRoleEnum::Driver,
                'phone' => $phone,
            ])->id,
            'operator_id' => $operator->id,
            'license_number' => $license,
            'license_class' => 'D',
            'license_expiry' => now()->addYears(3),
            'id_card_number' => 'CCCD-'.$license,
            'status' => 'verified',
        ]);
    };

    return [$operatorUser, $operator, $vehicle, $makeDriver('GPLX-A-1', '0931111101'), $makeDriver('GPLX-A-2', '0931111102')];
}

it('gỡ tài xế cũ khi cùng xe được gán lại cho tài xế mới', function (): void {
    [$operatorUser, $operator, $vehicle, $oldDriver, $newDriver] = vehicleAssignmentFixture();
    Sanctum::actingAs($operatorUser, ['*'], 'sanctum');
    Sanctum::actingAs($operatorUser, ['*'], 'operator');

    $oldDriver->update(['current_vehicle_id' => $vehicle->id]);

    $this->putJson("/api/operator/drivers/{$newDriver->id}/vehicle", [
        'vehicle_id' => $vehicle->id,
    ])->assertOk();

    expect($oldDriver->fresh()->current_vehicle_id)->toBeNull()
        ->and($newDriver->fresh()->current_vehicle_id)->toBe($vehicle->id)
        ->and(Driver::where('current_vehicle_id', $vehicle->id)->count())->toBe(1);
});

it('không cho nhà xe gán phương tiện thuộc nhà xe khác', function (): void {
    [$operatorUser, , , $driver] = vehicleAssignmentFixture();
    $otherUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $otherOperator = Operator::create([
        'user_id' => $otherUser->id,
        'company_name' => 'Nhà xe khác',
        'business_license' => 'GPKD-ASSIGN-2',
        'status' => 'verified',
    ]);
    $otherVehicle = Vehicle::create([
        'operator_id' => $otherOperator->id,
        'plate_number' => '30B-12345',
        'brand' => 'Ford', 'model' => 'Transit',
        'vehicle_type' => 'minibus_16', 'seat_count' => 16, 'status' => 'active',
    ]);
    Sanctum::actingAs($operatorUser, ['*'], 'sanctum');
    Sanctum::actingAs($operatorUser, ['*'], 'operator');

    $this->putJson("/api/operator/drivers/{$driver->id}/vehicle", [
        'vehicle_id' => $otherVehicle->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('vehicle_id');

    expect($driver->fresh()->current_vehicle_id)->toBeNull();
});

it('database từ chối hai tài xế cùng giữ một xe', function (): void {
    [, , $vehicle, $driverA, $driverB] = vehicleAssignmentFixture();
    $driverA->update(['current_vehicle_id' => $vehicle->id]);

    expect(fn () => $driverB->update(['current_vehicle_id' => $vehicle->id]))
        ->toThrow(QueryException::class);
});
