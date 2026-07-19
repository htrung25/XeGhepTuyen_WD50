<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;

function setupSameCitySearchContext(): void
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id,
        'company_name' => 'Same City Bus',
        'business_license' => 'GP-5151',
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
        'id_card_number' => '515151515151',
        'status' => 'verified',
    ]);

    Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => Carbon::parse('2026-07-20 08:00:00'),
        'arrive_at' => Carbon::parse('2026-07-20 10:30:00'),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);
}

it('rejects trip search when pickup city equals dropoff city', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-19 09:00:00'));
    setupSameCitySearchContext();

    $response = $this->getJson('/api/public/trips?'.http_build_query([
        'from_city' => 'Hà Nội',
        'to_city' => 'Hà Nội',
        'date' => '2026-07-20',
        'passengers' => 1,
    ]));

    // Input phi lý (điểm đi == điểm đến) phải bị từ chối ở tầng validate,
    // KHÔNG được lọt xuống query rồi trả 200 rỗng (gây hiểu nhầm "hết vé").
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('to_city');

    Carbon::setTestNow();
});

it('still returns trips when pickup and dropoff cities differ', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-19 09:00:00'));
    setupSameCitySearchContext();

    $response = $this->getJson('/api/public/trips?'.http_build_query([
        'from_city' => 'Hà Nội',
        'to_city' => 'Hải Phòng',
        'date' => '2026-07-20',
        'passengers' => 1,
    ]));

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    expect($response->json('data'))->toHaveCount(1);

    Carbon::setTestNow();
});
