<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use Illuminate\Support\Str;

/**
 * P2: hoàn tiền vé TIỀN MẶT không được trừ quỹ/ví nền tảng (tiền do nhà xe giữ — Phương án A).
 * Vé online thì vẫn hoàn về ví nội bộ như cũ.
 */
function makePaidBooking(string $method): Booking
{
    $opUser = User::factory()->create(['role' => UserRole::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP', 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN→HP', 'base_price' => 150000]);
    $pickup = RouteStop::create([
        'route_id' => $route->id, 'stop_name' => 'A', 'address' => 'HN', 'lat' => 21, 'lng' => 105.7, 'stop_order' => 1,
    ]);
    $dropoff = RouteStop::create([
        'route_id' => $route->id, 'stop_name' => 'B', 'address' => 'HP', 'lat' => 20.8, 'lng' => 106.6, 'stop_order' => 2,
    ]);
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
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addDay(), 'arrive_at' => now()->addDay()->addHours(2),
        'available_seats' => 9, 'price' => 150000, 'status' => 'scheduled',
    ]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $booking = Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('###'),
        'user_id' => $customer->id, 'trip_id' => $trip->id, 'pickup_stop_id' => $pickup->id,
        'dropoff_stop_id' => $dropoff->id, 'passenger_count' => 1, 'contact_name' => 'A', 'contact_phone' => '0900000000',
        'subtotal' => 150000, 'final_amount' => 150000, 'payment_method' => $method,
        'payment_status' => 'paid', 'booking_status' => 'confirmed', 'qr_token' => Str::random(32),
    ]);
    Payment::create([
        'booking_id' => $booking->id, 'user_id' => $customer->id, 'amount' => 150000,
        'method' => $method, 'status' => PaymentStatus::Success, 'gateway_order_id' => strtoupper(Str::random(10)),
    ]);

    return $booking;
}

it('hoàn vé TIỀN MẶT KHÔNG ghi có ví nền tảng', function () {
    $booking = makePaidBooking(PaymentMethod::Cash->value);

    app(PaymentService::class)->refund($booking, 150000);

    expect((int) ($booking->user->fresh()->wallet->balance ?? 0))->toBe(0);
    expect($booking->payment->fresh()->status->value)->toBe('refunded');
    expect($booking->fresh()->payment_status->value)->toBe('refunded');
});

it('hoàn vé ONLINE vẫn ghi có ví khách', function () {
    $booking = makePaidBooking(PaymentMethod::Momo->value);

    app(PaymentService::class)->refund($booking, 150000);

    expect((int) $booking->user->fresh()->wallet->balance)->toBe(150000);
    expect($booking->payment->fresh()->status->value)->toBe('refunded');
});
