<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\SeatMap;
use App\Models\ServiceArea;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Laravel\Sanctum\Sanctum;

function setupCustomBookingContext(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Long', 'business_license' => 'GP-1234', 'status' => 'verified',
    ]);

    $route = Route::create(['operator_id' => $operator->id, 'name' => 'Hà Nội - Hải Phòng', 'base_price' => 150000]);

    RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Bến xe Mỹ Đình', 'address' => 'Mỹ Đình, Hà Nội', 'lat' => 21.0285, 'lng' => 105.8544, 'stop_order' => 1]);
    RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Bến xe Cầu Rào', 'address' => 'Lạch Tray, Hải Phòng', 'lat' => 20.8449, 'lng' => 106.6881, 'stop_order' => 2]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-99999',
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);

    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-987654', 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => '123456789012', 'status' => 'verified',
    ]);

    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addHours(2), 'arrive_at' => now()->addHours(4),
        'available_seats' => 9, 'price' => 150000, 'status' => 'scheduled',
    ]);

    $seat = SeatMap::create([
        'trip_id' => $trip->id,
        'seat_code' => 'A1',
        'price' => 150000,
        'status' => 'available',
    ]);

    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);

    // Fail-closed (geofencing): route cần vùng active thì booking mới pass.
    ServiceArea::updateOrCreate(['code' => 'HN'], ['name' => 'Hà Nội', 'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true]);
    ServiceArea::updateOrCreate(['code' => 'HP'], ['name' => 'Hải Phòng', 'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true]);
    // refresh(): nạp origin_city/dest_city mặc định (default áp ở DB, model in-memory null)
    $route->refresh()->syncServiceAreasFromCities() && $route->save();

    return [$trip, $seat, $customer];
}

it('allows customer to book a trip using custom pickup and dropoff coordinates', function () {
    [$trip, $seat, $customer] = setupCustomBookingContext();

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    $response = $this->postJson('/api/customer/bookings', [
        'trip_id' => $trip->id,
        'seat_ids' => [$seat->id],
        'pickup_stop_id' => null,
        'dropoff_stop_id' => null,
        'pickup_address' => 'Số 10 Phạm Hùng, Cầu Giấy, Hà Nội',
        'pickup_lat' => 21.026543,
        'pickup_lng' => 105.789123,
        'dropoff_address' => 'Số 20 Lạch Tray, Ngô Quyền, Hải Phòng',
        'dropoff_lat' => 20.854123,
        'dropoff_lng' => 106.698765,
        'passenger_count' => 1,
        'contact_name' => 'Nguyễn Văn A',
        'contact_phone' => '0912345678',
        'payment_method' => 'cash',
        'passengers' => [
            ['full_name' => 'Nguyễn Văn A'],
        ],
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('success', true);

    $bookingId = $response->json('data.id');
    $booking = Booking::find($bookingId);

    expect($booking)->not->toBeNull();
    expect($booking->pickup_stop_id)->toBeNull();
    expect($booking->dropoff_stop_id)->toBeNull();
    expect($booking->pickup_address)->toBe('Số 10 Phạm Hùng, Cầu Giấy, Hà Nội');
    expect((float) $booking->pickup_lat)->toBe(21.026543);
    expect((float) $booking->pickup_lng)->toBe(105.789123);
    expect($booking->dropoff_address)->toBe('Số 20 Lạch Tray, Ngô Quyền, Hải Phòng');
    expect((float) $booking->dropoff_lat)->toBe(20.854123);
    expect((float) $booking->dropoff_lng)->toBe(106.698765);

    // Verify detail API resource mapping falls back correctly
    $detailResponse = $this->getJson("/api/customer/bookings/{$bookingId}");
    $detailResponse->assertStatus(200);
    $detailResponse->assertJsonPath('data.pickup_stop.stop_name', 'Điểm đón tùy chỉnh');
    $detailResponse->assertJsonPath('data.pickup_stop.address', 'Số 10 Phạm Hùng, Cầu Giấy, Hà Nội');
    $detailResponse->assertJsonPath('data.dropoff_stop.stop_name', 'Điểm trả tùy chỉnh');
    $detailResponse->assertJsonPath('data.dropoff_stop.address', 'Số 20 Lạch Tray, Ngô Quyền, Hải Phòng');

    // Verify QR code and URL are present and identical
    expect($booking->fresh()->qr_code)->not->toBeNull();
    // QR có thể được tạo nền trước, nhưng API không được lộ cho vé pending/unpaid.
    $detailResponse->assertJsonPath('data.can_access_ticket', false);
    $detailResponse->assertJsonPath('data.qr_code', null);
    $detailResponse->assertJsonPath('data.qr_url', null);
});
