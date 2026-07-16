<?php

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Support\Str;

/**
 * P8 (Hướng 2): khi chuyến hoàn tất, vé confirmed (đã xác nhận, kể cả tài xế quên
 * quét QR) → completed để ghi nhận doanh thu; chỉ pending → cancelled.
 */
function makeTripForCompletion(): Trip
{
    $opUser = User::factory()->create(['role' => UserRole::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP', 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN→HP', 'base_price' => 150000]);
    RouteStop::create(['route_id' => $route->id, 'stop_name' => 'A', 'address' => 'HN', 'lat' => 21, 'lng' => 105.7, 'stop_order' => 1]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $drvUser = User::factory()->create(['role' => UserRole::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);

    return Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->subHour(), 'arrive_at' => now()->addHour(),
        'available_seats' => 9, 'price' => 150000, 'status' => 'in_progress',
    ]);
}

function makeBookingOnTrip(Trip $trip, string $status, string $paymentStatus): Booking
{
    return Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('###'),
        'user_id' => User::factory()->create(['role' => UserRole::Customer])->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $trip->route->stops()->first()->id, 'dropoff_stop_id' => $trip->route->stops()->first()->id,
        'passenger_count' => 1, 'contact_name' => 'A', 'contact_phone' => '0900000000',
        'subtotal' => 150000, 'final_amount' => 150000, 'payment_method' => 'cash',
        'payment_status' => $paymentStatus, 'booking_status' => $status, 'qr_token' => Str::random(32),
    ]);
}

it('vé confirmed (quên check-in) → completed, không còn no_show', function () {
    $trip = makeTripForCompletion();
    $checkedIn = makeBookingOnTrip($trip, 'checked_in', 'paid');
    $confirmed = makeBookingOnTrip($trip, 'confirmed', 'paid');
    $pending = makeBookingOnTrip($trip, 'pending', 'unpaid');

    app(BookingService::class)->finalizeOnTripComplete($trip);

    expect($checkedIn->fresh()->booking_status->value)->toBe('completed');
    expect($confirmed->fresh()->booking_status->value)->toBe('completed');
    expect($pending->fresh()->booking_status->value)->toBe('cancelled');
});
