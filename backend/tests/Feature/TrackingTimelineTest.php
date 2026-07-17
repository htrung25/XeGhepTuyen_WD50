<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/** Dựng chuyến HN→HP với 4 điểm dừng có offset_minutes và 1 booking gắn stop đón/trả. */
function setupTrackingContext(string $tripStatus, CarbonInterface $departAt): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Track', 'business_license' => 'GP-5678', 'status' => 'verified',
    ]);

    $route = Route::create(['operator_id' => $operator->id, 'name' => 'Hà Nội - Hải Phòng', 'base_price' => 150000]);

    $myDinh = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Mỹ Đình', 'address' => 'Mỹ Đình, HN', 'lat' => 21.02, 'lng' => 105.85, 'stop_order' => 1, 'offset_minutes' => 0, 'is_pickup' => true]);
    $cauGiay = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Cầu Giấy', 'address' => 'Cầu Giấy, HN', 'lat' => 21.03, 'lng' => 105.79, 'stop_order' => 2, 'offset_minutes' => 15, 'is_pickup' => true]);
    RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Gia Lâm', 'address' => 'Gia Lâm, HN', 'lat' => 21.04, 'lng' => 105.93, 'stop_order' => 3, 'offset_minutes' => 35, 'is_pickup' => true]);
    $trungTamHp = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Trung tâm HP', 'address' => 'Lê Chân, HP', 'lat' => 20.84, 'lng' => 106.68, 'stop_order' => 4, 'offset_minutes' => 115, 'is_dropoff' => true]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-11111',
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);

    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-111222', 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => '111222333444', 'status' => 'verified',
    ]);

    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => $departAt, 'arrive_at' => $departAt->addHours(2),
        'available_seats' => 9, 'price' => 150000, 'status' => $tripStatus,
    ]);

    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $booking = Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
        'user_id' => $customer->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $cauGiay->id, 'dropoff_stop_id' => $trungTamHp->id, 'passenger_count' => 1,
        'contact_name' => 'A', 'contact_phone' => '0900000000', 'subtotal' => 150000, 'final_amount' => 150000,
        'payment_method' => 'momo', 'payment_status' => 'paid', 'booking_status' => 'confirmed', 'qr_token' => Str::random(32),
    ]);

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    return [$booking, $myDinh, $cauGiay, $trungTamHp];
}

it('trả timeline điểm dừng với trạng thái done/current/upcoming khi xe đang chạy', function () {
    // Xe xuất phát 20 phút trước → Mỹ Đình (+0') và Cầu Giấy (+15') đã qua, Gia Lâm (+35') là điểm đang đến.
    [$booking] = setupTrackingContext('in_progress', now()->subMinutes(20));

    $response = $this->getJson("/api/customer/bookings/{$booking->id}/track");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.booking_code', $booking->booking_code)
        ->assertJsonPath('data.vehicle.plate_number', '30A-11111')
        ->assertJsonCount(4, 'data.stops')
        ->assertJsonPath('data.stops.0.name', 'Mỹ Đình')
        ->assertJsonPath('data.stops.0.status', 'done')
        ->assertJsonPath('data.stops.1.status', 'done')
        ->assertJsonPath('data.stops.2.status', 'current')
        ->assertJsonPath('data.stops.3.status', 'upcoming');
});

it('đánh dấu điểm đón và điểm trả của chính hành khách trong timeline', function () {
    [$booking] = setupTrackingContext('in_progress', now()->subMinutes(20));

    $response = $this->getJson("/api/customer/bookings/{$booking->id}/track");

    $response->assertOk()
        // Cầu Giấy (index 1) là điểm đón của booking, Trung tâm HP (index 3) là điểm trả.
        ->assertJsonPath('data.stops.1.is_your_pickup', true)
        ->assertJsonPath('data.stops.1.is_your_dropoff', false)
        ->assertJsonPath('data.stops.3.is_your_dropoff', true)
        ->assertJsonPath('data.stops.0.is_your_pickup', false);
});

it('trả mọi điểm dừng là upcoming khi chuyến chưa khởi hành và done khi đã hoàn thành', function () {
    [$booking] = setupTrackingContext('scheduled', now()->addHours(2));
    $this->getJson("/api/customer/bookings/{$booking->id}/track")
        ->assertOk()
        ->assertJsonPath('data.stops.0.status', 'upcoming')
        ->assertJsonPath('data.stops.3.status', 'upcoming');

    $booking->trip->update(['status' => 'completed']);
    $this->getJson("/api/customer/bookings/{$booking->id}/track")
        ->assertOk()
        ->assertJsonPath('data.stops.0.status', 'done')
        ->assertJsonPath('data.stops.3.status', 'done');
});

it('giữ điểm cuối là current khi xe trễ mọi mốc giờ nhưng chuyến chưa kết thúc', function () {
    // Xuất phát 5 tiếng trước → mọi offset đều đã qua, nhưng trip vẫn in_progress.
    [$booking] = setupTrackingContext('in_progress', now()->subHours(5));

    $this->getJson("/api/customer/bookings/{$booking->id}/track")
        ->assertOk()
        ->assertJsonPath('data.stops.0.status', 'done')
        ->assertJsonPath('data.stops.2.status', 'done')
        ->assertJsonPath('data.stops.3.status', 'current');
});

it('chặn xem tracking vé của người khác', function () {
    [$booking] = setupTrackingContext('in_progress', now()->subMinutes(20));

    $other = User::factory()->create(['role' => UserRoleEnum::Customer]);
    Sanctum::actingAs($other, ['*'], 'sanctum');
    Sanctum::actingAs($other, ['*'], 'customer');

    $this->getJson("/api/customer/bookings/{$booking->id}/track")->assertNotFound();
});
