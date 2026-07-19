<?php

use App\DTOs\GeoCoordinate;
use App\Enums\UserRoleEnum;
use App\Exceptions\LocationOutsideServiceAreaException;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\SeatMap;
use App\Models\ServiceArea;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GeometryFactory;
use App\Services\ServiceAreaService;
use Laravel\Sanctum\Sanctum;

function setupServiceAreaBookingContext(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Geo', 'business_license' => 'GP-5678', 'status' => 'verified',
    ]);

    $route = Route::create(['operator_id' => $operator->id, 'name' => 'Hà Nội - Hải Phòng', 'base_price' => 150000]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-88888',
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);

    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-111222', 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => '999888777666', 'status' => 'verified',
    ]);

    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addHours(2), 'arrive_at' => now()->addHours(4),
        'available_seats' => 9, 'price' => 150000, 'status' => 'scheduled',
    ]);

    $seat = SeatMap::create([
        'trip_id' => $trip->id, 'seat_code' => 'A1', 'price' => 150000, 'status' => 'available',
    ]);

    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);

    // Fail-closed: route phải có vùng active thì luồng booking mới pass. Route
    // được tạo trước khi có area (observer sync ra null) → gọi lại sync + save.
    ServiceArea::updateOrCreate(['code' => 'HN'], ['name' => 'Hà Nội', 'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true]);
    ServiceArea::updateOrCreate(['code' => 'HP'], ['name' => 'Hải Phòng', 'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true]);
    $trip->route->syncServiceAreasFromCities() && $trip->route->save();

    return [$trip, $seat, $customer];
}

function serviceAreaBookingPayload(Trip $trip, SeatMap $seat): array
{
    return [
        'trip_id' => $trip->id,
        'seat_ids' => [$seat->id],
        'pickup_address' => 'Số 10 Phạm Hùng, Cầu Giấy, Hà Nội',
        'pickup_lat' => 21.026543,
        'pickup_lng' => 105.789123,
        'dropoff_address' => 'Số 20 Lạch Tray, Ngô Quyền, Hải Phòng',
        'dropoff_lat' => 20.854123,
        'dropoff_lng' => 106.698765,
        'passenger_count' => 1,
        'contact_name' => 'Nguyễn Văn B',
        'contact_phone' => '0912345679',
        'payment_method' => 'cash',
        'passengers' => [['full_name' => 'Nguyễn Văn B']],
    ];
}

// ─── GeometryFactory ghi tọa độ theo driver ──────────────────────────────────

it('coordinateAttributes trên sqlite (test) ghi cặp lat/lng vật lý', function () {
    $attrs = app(GeometryFactory::class)
        ->coordinateAttributes('pickup', GeoCoordinate::fromLatLng(21.026543, 105.789123));

    expect($attrs)->toBe(['pickup_lat' => 21.026543, 'pickup_lng' => 105.789123]);
});

// ─── Luồng đặt vé ────────────────────────────────────────────────────────────

it('trả 422 kèm code LOCATION_OUTSIDE_SERVICE_AREA khi điểm đón ngoài vùng', function () {
    [$trip, $seat, $customer] = setupServiceAreaBookingContext();

    $this->mock(ServiceAreaService::class)
        ->shouldReceive('validateBookingLocations')
        ->once()
        ->andThrow(new LocationOutsideServiceAreaException('Điểm đón nằm ngoài vùng phục vụ (Hà Nội) của tuyến'));

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    $this->postJson('/api/customer/bookings', serviceAreaBookingPayload($trip, $seat))
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'code' => 'LOCATION_OUTSIDE_SERVICE_AREA',
            'message' => 'Điểm đón nằm ngoài vùng phục vụ (Hà Nội) của tuyến',
        ]);

    expect(Booking::count())->toBe(0);
});

it('chặn đảo trục ở tầng HTTP: lat mang giá trị kinh độ VN (~105) bị 422 từ validation', function () {
    [$trip, $seat, $customer] = setupServiceAreaBookingContext();

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    $payload = serviceAreaBookingPayload($trip, $seat);
    // Mô phỏng client đảo (lng, lat) → (lat, lng)
    [$payload['pickup_lat'], $payload['pickup_lng']] = [$payload['pickup_lng'], $payload['pickup_lat']];

    $this->postJson('/api/customer/bookings', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pickup_lat']);

    expect(Booking::count())->toBe(0);
});

it('tạo booking bình thường khi vị trí hợp lệ (geofencing pass) và lưu đúng tọa độ', function () {
    [$trip, $seat, $customer] = setupServiceAreaBookingContext();

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    $this->postJson('/api/customer/bookings', serviceAreaBookingPayload($trip, $seat))
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    // Đọc lại qua tên cột lat/lng (MySQL: generated column từ POINT; SQLite: cột vật lý)
    $booking = Booking::sole();
    expect((float) $booking->pickup_lat)->toBe(21.026543)
        ->and((float) $booking->pickup_lng)->toBe(105.789123)
        ->and((float) $booking->dropoff_lat)->toBe(20.854123)
        ->and((float) $booking->dropoff_lng)->toBe(106.698765);
});
