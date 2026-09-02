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
use Illuminate\Support\Str;

/**
 * Bảng kê thu nhập tài xế: lịch sử phải lọc theo ĐÚNG kỳ đang chọn, trước đây
 * endpoint bỏ qua period nên đổi bộ lọc mà danh sách vẫn y nguyên.
 */
function makeDriverWithTrips(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP',
        'commission_rate' => 10, 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN→HP', 'base_price' => 150000]);
    $stop = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'A', 'address' => 'HN', 'lat' => 21, 'lng' => 105.7, 'stop_order' => 1]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $driverUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $driverUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);

    // Một chuyến hoàn thành HÔM NAY, một chuyến hoàn thành THÁNG TRƯỚC.
    $mkTrip = function ($completedAt) use ($route, $vehicle, $driver, $stop) {
        $trip = Trip::create([
            'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            'depart_at' => $completedAt->copy()->subHours(2), 'arrive_at' => $completedAt,
            'available_seats' => 9, 'price' => 150000, 'status' => 'completed',
            'completed_at' => $completedAt,
        ]);
        Booking::create([
            'booking_code' => 'HNHP'.fake()->unique()->numerify('########'),
            'user_id' => User::factory()->create(['role' => UserRoleEnum::Customer])->id,
            'trip_id' => $trip->id, 'pickup_stop_id' => $stop->id, 'dropoff_stop_id' => $stop->id,
            'passenger_count' => 1, 'contact_name' => 'A', 'contact_phone' => '0900000000',
            'subtotal' => 150000, 'final_amount' => 150000, 'payment_method' => 'momo',
            'payment_status' => 'paid', 'booking_status' => 'completed', 'qr_token' => Str::random(32),
        ]);

        return $trip;
    };

    $mkTrip(now()->startOfDay()->addHours(9));
    $mkTrip(now()->subMonth()->startOfMonth()->addDays(2));

    return [$driver, ['Authorization' => 'Bearer '.$driverUser->createToken('driver_token')->plainTextToken]];
}

it('lịch sử thu nhập lọc theo kỳ "hôm nay"', function () {
    [, $headers] = makeDriverWithTrips();

    $res = $this->getJson('/api/driver/earnings/transactions?period=today', $headers)
        ->assertOk();

    // Chỉ chuyến hoàn thành hôm nay, KHÔNG kèm chuyến tháng trước.
    expect($res->json('meta.total'))->toBe(1);
    expect($res->json('data'))->toHaveCount(1);
});

it('lịch sử thu nhập kỳ "tháng này" bỏ qua chuyến của tháng trước', function () {
    [, $headers] = makeDriverWithTrips();

    $res = $this->getJson('/api/driver/earnings/transactions?period=month', $headers)
        ->assertOk();

    expect($res->json('meta.total'))->toBe(1);
});

it('lịch sử thu nhập trả last_page để client phân trang đúng', function () {
    [, $headers] = makeDriverWithTrips();

    $this->getJson('/api/driver/earnings/transactions?period=today', $headers)
        ->assertOk()
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});
