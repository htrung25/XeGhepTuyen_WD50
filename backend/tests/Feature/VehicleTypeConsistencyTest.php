<?php

use App\Enums\UserRoleEnum;
use App\Enums\VehicleTypeEnum;
use App\Models\Operator;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripService;
use Laravel\Sanctum\Sanctum;

it('quy định đúng số ghế và sơ đồ ghế cho limousine 12 và minibus 16', function (): void {
    $limousine = new Vehicle(['vehicle_type' => VehicleTypeEnum::Limousine12]);
    $minibus = new Vehicle(['vehicle_type' => VehicleTypeEnum::Minibus16]);

    $limousineSeats = TripService::getSeatTemplate($limousine);
    $minibusSeats = TripService::getSeatTemplate($minibus);

    expect(VehicleTypeEnum::Limousine12->seatCount())->toBe(12)
        ->and($limousineSeats)->toHaveCount(12)
        ->and(array_unique($limousineSeats))->toHaveCount(12)
        ->and(VehicleTypeEnum::Minibus16->seatCount())->toBe(16)
        ->and($minibusSeats)->toHaveCount(16)
        ->and(array_unique($minibusSeats))->toHaveCount(16);
});

it('BE tự gán 12 chỗ khi operator tạo limousine 12', function (): void {
    $user = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $user->id,
        'company_name' => 'Nhà xe Limousine',
        'business_license' => 'GPKD-LIMO-12',
        'status' => 'verified',
    ]);

    Sanctum::actingAs($user, ['*'], 'sanctum');
    Sanctum::actingAs($user, ['*'], 'operator');

    $this->postJson('/api/operator/vehicles', [
        'plate_number' => '29F-12012',
        'vehicle_type' => 'limousine_12',
        'seat_count' => 16,
        'brand' => 'Hyundai',
        'model' => 'Solati Limousine',
        'year' => 2025,
        'color' => 'Đen',
        'registration_expiry' => now()->addYear()->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.vehicle_type', 'limousine_12')
        ->assertJsonPath('data.seat_count', 12);

    $vehicle = Vehicle::where('operator_id', $operator->id)->sole();
    expect($vehicle->vehicle_type)->toBe(VehicleTypeEnum::Limousine12)
        ->and($vehicle->seat_count)->toBe(12);
});

it('migration chỉ đổi xe minibus cấu hình 12 chỗ sang limousine 12', function (): void {
    $user = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $user->id,
        'company_name' => 'Nhà xe Migration',
        'business_license' => 'GPKD-LIMO-MIGRATION',
        'status' => 'verified',
    ]);

    $legacy12 = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29F-12121',
        'brand' => 'Hyundai',
        'model' => 'Solati',
        'vehicle_type' => 'minibus_16',
        'seat_count' => 12,
        'status' => 'active',
    ]);
    $real16 = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29F-16161',
        'brand' => 'Ford',
        'model' => 'Transit',
        'vehicle_type' => 'minibus_16',
        'seat_count' => 16,
        'status' => 'active',
    ]);

    $migration = require database_path(
        'migrations/2026_08_18_000002_add_limousine_12_vehicle_type.php'
    );
    $migration->up();

    expect($legacy12->fresh()->vehicle_type)->toBe(VehicleTypeEnum::Limousine12)
        ->and($legacy12->fresh()->seat_count)->toBe(12)
        ->and($real16->fresh()->vehicle_type)->toBe(VehicleTypeEnum::Minibus16)
        ->and($real16->fresh()->seat_count)->toBe(16);
});
