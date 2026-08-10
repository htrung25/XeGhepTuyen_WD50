<?php

use App\Enums\BookingStatusEnum;
use App\Enums\DriverStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TripStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PaymentService;
use App\Services\TripService;
use Illuminate\Support\Str;

/**
 * Tài xế có thể bấm "bắt đầu chuyến" SỚM hơn depart_at (không có giới hạn thời gian ở
 * TripService::startTrip). Nếu khách đã tạo booking Pending TỪ TRƯỚC lúc chuyến còn
 * Scheduled, PaymentService::initiate chỉ check depart_at->isPast() thì vẫn cho thanh
 * toán/confirm dù chuyến đã InProgress — tạo vé cho một chuyến xe đã chạy.
 */
function payGuardCtx(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Test', 'business_license' => 'GP-'.Str::random(6), 'status' => 'verified',
    ]);

    $route = Route::create([
        'operator_id' => $operator->id, 'name' => 'HN→HP', 'origin_city' => 'Hà Nội', 'dest_city' => 'Hải Phòng',
        'distance_km' => 105, 'est_duration_min' => 120, 'base_price' => 120000, 'is_active' => true,
    ]);
    $stop = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Nước Ngầm', 'address' => 'HN', 'lat' => 21, 'lng' => 105.8, 'stop_order' => 1]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '29A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);

    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'D',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'),
        'status' => DriverStatusEnum::Verified,
    ]);

    return ['route' => $route, 'stop' => $stop, 'vehicle' => $vehicle, 'driver' => $driver];
}

function payGuardTrip(array $c): Trip
{
    return Trip::create([
        'route_id' => $c['route']->id, 'vehicle_id' => $c['vehicle']->id, 'driver_id' => $c['driver']->id,
        'depart_at' => now()->addHours(3), 'arrive_at' => now()->addHours(5),
        'available_seats' => 9, 'price' => 120000, 'status' => TripStatusEnum::Scheduled,
    ]);
}

function payGuardPendingBooking(Trip $trip, array $c): Booking
{
    return Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $c['stop']->id, 'dropoff_stop_id' => $c['stop']->id,
        'passenger_count' => 1, 'contact_name' => 'K', 'contact_phone' => '0900000000',
        'subtotal' => 120000, 'final_amount' => 120000, 'payment_method' => 'momo',
        'payment_status' => 'unpaid', 'booking_status' => 'pending', 'qr_token' => Str::random(32),
        'expires_at' => now()->addMinutes(15),
    ]);
}

it('initiate từ chối thanh toán khi chuyến đã InProgress dù depart_at còn ở tương lai', function () {
    $c = payGuardCtx();
    $trip = payGuardTrip($c);
    $booking = payGuardPendingBooking($trip, $c);

    // Tài xế bấm "bắt đầu chuyến" sớm — depart_at vẫn còn cách hiện tại 3 giờ.
    app(TripService::class)->startTrip($trip->id, $c['driver']->id);
    expect($trip->fresh()->status)->toBe(TripStatusEnum::InProgress);
    expect($trip->fresh()->depart_at->isPast())->toBeFalse();

    expect(fn () => app(PaymentService::class)->initiate($booking->fresh(), PaymentMethodEnum::Momo))
        ->toThrow(InvalidArgumentException::class, 'Chuyến đã khởi hành, không thể thanh toán vé này');

    expect($booking->fresh()->booking_status)->toBe(BookingStatusEnum::Pending);
});

it('initiate vẫn cho thanh toán bình thường khi chuyến còn Scheduled', function () {
    $c = payGuardCtx();
    $trip = payGuardTrip($c);
    $booking = payGuardPendingBooking($trip, $c);

    $result = app(PaymentService::class)->initiate($booking->fresh(), PaymentMethodEnum::Cash);

    expect($result['status'])->toBe('confirmed_unpaid');
    expect($booking->fresh()->booking_status)->toBe(BookingStatusEnum::Confirmed);
});
