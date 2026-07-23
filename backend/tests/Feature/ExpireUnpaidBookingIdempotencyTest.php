<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SeatMap;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Support\Str;

/**
 * Backlog queue `high` (job expire tồn đọng) chỉ an toàn khi drain nếu expire()
 * idempotent. Trước đây guard chỉ xét expires_at + payment_status → vé đã hủy
 * bằng đường khác vẫn thỏa điều kiện → increment available_seats lần hai.
 */
function makeExpiredUnpaidBooking(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Expire',
        'business_license' => 'GP-'.fake()->unique()->numerify('####'), 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN - HP', 'base_price' => 150000]);
    $stop = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'A', 'address' => 'HN', 'lat' => 21, 'lng' => 105.7, 'stop_order' => 1]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addHours(5), 'arrive_at' => now()->addHours(7),
        'available_seats' => 8, 'price' => 150000, 'status' => 'scheduled',
    ]);
    $seat = SeatMap::create(['trip_id' => $trip->id, 'seat_code' => 'A1', 'price' => 150000, 'status' => 'booked']);

    $booking = Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id,
        'trip_id' => $trip->id, 'pickup_stop_id' => $stop->id, 'dropoff_stop_id' => $stop->id,
        'passenger_count' => 1, 'contact_name' => 'A', 'contact_phone' => '0900000000',
        'subtotal' => 150000, 'final_amount' => 150000, 'payment_method' => 'momo',
        'payment_status' => 'unpaid', 'booking_status' => 'pending',
        'qr_token' => Str::random(32), 'expires_at' => now()->subMinute(), // đã quá hạn
    ]);

    return [$trip, $booking];
}

it('expire() hủy vé quá hạn và trả ghế đúng 1 lần', function () {
    [$trip, $booking] = makeExpiredUnpaidBooking();

    app(BookingService::class)->expire($booking);

    expect($trip->refresh()->available_seats)->toBe(9)
        ->and($booking->refresh()->booking_status->value)->toBe('cancelled');
});

it('chạy expire() lần hai KHÔNG cộng thêm ghế (an toàn khi drain backlog)', function () {
    [$trip, $booking] = makeExpiredUnpaidBooking();
    $service = app(BookingService::class);

    $service->expire($booking);
    $service->expire($booking->refresh());   // job trùng trong backlog
    $service->expire($booking->refresh());   // lần ba cho chắc

    expect($trip->refresh()->available_seats)->toBe(9); // KHÔNG phải 11
});

it('vé đã hủy bằng đường khác thì expire() không cộng ghế lần nữa', function () {
    [$trip, $booking] = makeExpiredUnpaidBooking();

    // Mô phỏng luồng hủy khác: set cancelled + trả ghế, KHÔNG đổi payment_status/expires_at
    $booking->update(['booking_status' => 'cancelled', 'cancelled_at' => now()]);
    $trip->increment('available_seats');
    expect($trip->refresh()->available_seats)->toBe(9);

    app(BookingService::class)->expire($booking->refresh());

    expect($trip->refresh()->available_seats)->toBe(9); // guard booking_status chặn cộng lần hai
});
