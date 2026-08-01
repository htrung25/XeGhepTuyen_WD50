<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;

/**
 * §4.4: GPS tài xế tối đa 1 lần / 10 giây (Redis rate-limit) — chống spam server.
 */
it('chặn cập nhật GPS quá 1 lần trong 10 giây (429)', function () {
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP', 'status' => 'verified',
    ]);
    $user = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $user->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN - HP', 'base_price' => 150000]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->subMinutes(10), 'arrive_at' => now()->addHours(2),
        'available_seats' => 9, 'price' => 150000, 'status' => 'in_progress',
    ]);

    // LocationController dùng auth('driver') → cần token thật.
    $headers = ['Authorization' => 'Bearer '.$user->createToken('driver_token')->plainTextToken];
    $payload = ['trip_id' => $trip->id, 'lat' => 21.0, 'lng' => 105.8];

    $this->postJson('/api/driver/location', $payload, $headers)->assertOk();
    // Lần 2 trong cùng 10 giây → bị chặn.
    $this->postJson('/api/driver/location', $payload, $headers)->assertStatus(429);
});
