<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('trả stop_order và số nhà xe để frontend điều hướng và gọi hỗ trợ', function () {
    $operatorUser = User::factory()->create([
        'role' => UserRoleEnum::Operator,
        'phone' => '0901112233',
    ]);
    $operator = Operator::create([
        'user_id' => $operatorUser->id,
        'company_name' => 'Nhà xe Navigation',
        'business_license' => 'NAV-001',
        'status' => 'verified',
    ]);
    $route = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hà Nội → Hải Phòng',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'base_price' => 120000,
    ]);
    $pickup = RouteStop::create([
        'route_id' => $route->id,
        'stop_name' => 'Bến xe Giáp Bát',
        'address' => 'Giáp Bát, Hà Nội',
        'lat' => 20.9847,
        'lng' => 105.8479,
        'stop_order' => 2,
        'is_pickup' => true,
    ]);
    $dropoff = RouteStop::create([
        'route_id' => $route->id,
        'stop_name' => 'Trung tâm Hải Phòng',
        'address' => 'Lạch Tray, Hải Phòng',
        'lat' => 20.8529,
        'lng' => 106.6877,
        'stop_order' => 4,
        'is_dropoff' => true,
    ]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29A-99999',
        'brand' => 'Ford',
        'model' => 'Transit',
        'vehicle_type' => 'van_9',
        'seat_count' => 9,
    ]);
    $driverUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-NAV-001',
        'license_class' => 'B2',
        'license_expiry' => now()->addYears(2),
        'id_card_number' => '001999999999',
        'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'depart_at' => now()->addHour(),
        'arrive_at' => now()->addHours(3),
        'available_seats' => 8,
        'price' => 120000,
        'status' => 'in_progress',
    ]);
    $seat = SeatMap::create([
        'trip_id' => $trip->id,
        'seat_code' => 'A1',
        'seat_type' => 'standard',
        'price' => 120000,
        'status' => 'booked',
    ]);
    $booking = Booking::create([
        'booking_code' => 'NAV'.fake()->unique()->numerify('#######'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id,
        'trip_id' => $trip->id,
        'pickup_stop_id' => $pickup->id,
        'dropoff_stop_id' => $dropoff->id,
        'pickup_address' => $pickup->address,
        'pickup_lat' => $pickup->lat,
        'pickup_lng' => $pickup->lng,
        'dropoff_address' => $dropoff->address,
        'dropoff_lat' => $dropoff->lat,
        'dropoff_lng' => $dropoff->lng,
        'passenger_count' => 1,
        'contact_name' => 'Khách Navigation',
        'contact_phone' => '0900000000',
        'subtotal' => 120000,
        'final_amount' => 120000,
        'payment_method' => 'cash',
        'payment_status' => 'unpaid',
        'booking_status' => 'confirmed',
        'qr_token' => Str::random(32),
    ]);
    BookingPassenger::create([
        'booking_id' => $booking->id,
        'seat_map_id' => $seat->id,
        'full_name' => 'Khách Navigation',
        'phone' => '0900000000',
        'is_primary' => true,
    ]);

    Sanctum::actingAs($driverUser, ['*'], 'sanctum');
    Sanctum::actingAs($driverUser, ['*'], 'driver');

    $this->getJson("/api/driver/trips/{$trip->id}")
        ->assertOk()
        ->assertJsonPath('data.operator.phone', '0901112233');

    $this->getJson("/api/driver/trips/{$trip->id}/passengers")
        ->assertOk()
        ->assertJsonPath('data.0.pickup_stop.stop_order', 2)
        ->assertJsonPath('data.0.dropoff_stop.stop_order', 4);
});
