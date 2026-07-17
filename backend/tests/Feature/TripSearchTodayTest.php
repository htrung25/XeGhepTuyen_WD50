<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;

function setupSearchTodayTestContext(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id,
        'company_name' => 'Tìm Kiếm Bus',
        'business_license' => 'GP-7777',
        'status' => 'verified',
    ]);

    $route = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hà Nội - Hải Phòng',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'base_price' => 150000,
    ]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '30A-'.rand(10000, 99999),
        'brand' => 'Ford',
        'model' => 'Transit',
        'vehicle_type' => 'van_9',
        'seat_count' => 9,
    ]);

    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-'.rand(100000, 999999),
        'license_class' => 'B2',
        'license_expiry' => now()->addYears(3),
        'id_card_number' => '777777777777',
        'status' => 'verified',
    ]);

    return [$route, $vehicle, $driver];
}

it('returns tomorrow early trips when searching for today late at night', function () {
    [$route, $vehicle, $driver] = setupSearchTodayTestContext();

    // Mock current time to 23:30 (11:30 PM) on 2026-07-10
    $now = Carbon::parse('2026-07-10 23:30:00');
    Carbon::setTestNow($now);

    // Trip A: Departs today at 23:45 (Only 15 minutes away, should be unavailable because lead time > 30m)
    $tripA = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => Carbon::parse('2026-07-10 23:45:00'),
        'arrive_at' => Carbon::parse('2026-07-11 02:15:00'),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);

    // Trip B: Departs tomorrow at 01:30 (2 hours away, should be available and returned under today search)
    $tripB = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => Carbon::parse('2026-07-11 01:30:00'),
        'arrive_at' => Carbon::parse('2026-07-11 04:00:00'),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);

    // Make request search for "today" (2026-07-10)
    $response = $this->getJson('/api/public/trips?'.http_build_query([
        'from_city' => 'Hà Nội',
        'to_city' => 'Hải Phòng',
        'date' => '2026-07-10',
        'passengers' => 1,
    ]));

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);

    $data = $response->json('data');

    // Should return only Trip B (Trip A is too close, and Trip B departs tomorrow but falls within extended search range)
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($tripB->id);
    expect($data[0]['depart_at'])->toBe('2026-07-11 01:30:00');

    // Clean up mock
    Carbon::setTestNow();
});

it('strictly returns tomorrow trips when searching for tomorrow date', function () {
    [$route, $vehicle, $driver] = setupSearchTodayTestContext();

    // Mock current time to 23:30 (11:30 PM) on 2026-07-10
    $now = Carbon::parse('2026-07-10 23:30:00');
    Carbon::setTestNow($now);

    // Trip A: Departs tomorrow (2026-07-11) at 01:30
    $tripA = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => Carbon::parse('2026-07-11 01:30:00'),
        'arrive_at' => Carbon::parse('2026-07-11 04:00:00'),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);

    // Trip B: Departs day after tomorrow (2026-07-12) at 01:30
    $tripB = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => Carbon::parse('2026-07-12 01:30:00'),
        'arrive_at' => Carbon::parse('2026-07-12 04:00:00'),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);

    // Make request search for "tomorrow" (2026-07-11)
    $response = $this->getJson('/api/public/trips?'.http_build_query([
        'from_city' => 'Hà Nội',
        'to_city' => 'Hải Phòng',
        'date' => '2026-07-11',
        'passengers' => 1,
    ]));

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);

    $data = $response->json('data');

    // Should return only Trip A (date matches exactly 2026-07-11)
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($tripA->id);

    Carbon::setTestNow();
});
