<?php

use App\Enums\SeatStatusEnum;
use App\Enums\SeatTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Laravel\Sanctum\Sanctum;

function setupSeatLockTestContext(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id,
        'company_name' => 'Xe Ghep Test Company',
        'business_license' => 'GP-'.rand(1000, 9999),
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
        'id_card_number' => '888888888888',
        'status' => 'verified',
    ]);

    $trip = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => now()->addDays(2)->setTime(10, 0),
        'arrive_at' => now()->addDays(2)->setTime(12, 30),
        'available_seats' => 9,
        'price' => 150000,
        'status' => 'scheduled',
    ]);

    // Create seat maps
    $seatCodes = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
    $seats = [];
    foreach ($seatCodes as $code) {
        $seats[] = SeatMap::create([
            'trip_id' => $trip->id,
            'seat_code' => $code,
            'seat_type' => SeatTypeEnum::Standard,
            'price' => 150000,
            'status' => SeatStatusEnum::Available,
        ]);
    }

    return [$trip, $seats];
}

it('allows customer to lock seats and re-lock the same seats without error', function () {
    [$trip, $seats] = setupSeatLockTestContext();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);

    $bookingService = app(BookingService::class);
    $seatIds = [$seats[0]->id, $seats[1]->id]; // A1, A2

    // First lock
    $bookingService->lockSeats($seatIds, $customer->id, $trip->id);

    // Verify in DB
    expect(SeatMap::find($seats[0]->id)->status)->toBe(SeatStatusEnum::Locked);
    expect(SeatMap::find($seats[0]->id)->locked_by)->toBe($customer->id);

    // Second lock (re-lock same seats) should succeed without exceptions
    $bookingService->lockSeats($seatIds, $customer->id, $trip->id);

    expect(SeatMap::find($seats[0]->id)->status)->toBe(SeatStatusEnum::Locked);
    expect(SeatMap::find($seats[0]->id)->locked_by)->toBe($customer->id);
});

it('automatically releases old locks when customer locks new seats', function () {
    [$trip, $seats] = setupSeatLockTestContext();
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);

    $bookingService = app(BookingService::class);

    // Lock A1, A2
    $bookingService->lockSeats([$seats[0]->id, $seats[1]->id], $customer->id, $trip->id);
    expect(SeatMap::find($seats[0]->id)->status)->toBe(SeatStatusEnum::Locked);

    // Lock B1, B2 instead
    $bookingService->lockSeats([$seats[2]->id, $seats[3]->id], $customer->id, $trip->id);

    // Old seats A1, A2 must be released
    expect(SeatMap::find($seats[0]->id)->status)->toBe(SeatStatusEnum::Available);
    expect(SeatMap::find($seats[0]->id)->locked_by)->toBeNull();

    // New seats B1, B2 must be locked
    expect(SeatMap::find($seats[2]->id)->status)->toBe(SeatStatusEnum::Locked);
    expect(SeatMap::find($seats[2]->id)->locked_by)->toBe($customer->id);
});

it('returns available status for seats locked by the current customer but locked for others', function () {
    [$trip, $seats] = setupSeatLockTestContext();
    $customer1 = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $customer2 = User::factory()->create(['role' => UserRoleEnum::Customer]);

    $bookingService = app(BookingService::class);

    // Customer 1 locks A1
    $bookingService->lockSeats([$seats[0]->id], $customer1->id, $trip->id);

    // Get seats as Customer 1
    Sanctum::actingAs($customer1);
    auth()->guard('customer')->setUser($customer1);

    $response1 = $this->getJson("/api/customer/trips/{$trip->id}/seats");
    $response1->assertStatus(200);

    $seatsData1 = $response1->json('data');
    $seatA1Customer1 = collect($seatsData1)->firstWhere('seat_code', 'A1');
    // For Customer 1, it should show as available so they can select it
    expect($seatA1Customer1['status'])->toBe('available');

    // Get seats as Customer 2
    Sanctum::actingAs($customer2);
    auth()->guard('customer')->setUser($customer2);

    $response2 = $this->getJson("/api/customer/trips/{$trip->id}/seats");
    $response2->assertStatus(200);

    $seatsData2 = $response2->json('data');
    $seatA1Customer2 = collect($seatsData2)->firstWhere('seat_code', 'A1');
    // For Customer 2, it must show as locked
    expect($seatA1Customer2['status'])->toBe('locked');
});
