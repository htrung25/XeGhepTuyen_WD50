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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Dựng 1 nhà xe đã verify + 1 chuyến completed + N vé completed+paid (online/cash, 150.000đ,
 * commission 10%) để test doanh thu/quyết toán. Trả về Operator (->user là tài khoản nhà xe).
 */
function makeOperatorWithRevenue(int $online, int $cash, int $amount = 150000): Operator
{
    $opUser = User::factory()->create(['role' => UserRole::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP',
        'commission_rate' => 10, 'status' => 'verified',
    ]);
    $route = Route::create(['operator_id' => $operator->id, 'name' => 'HN→HP', 'base_price' => $amount]);
    $stop = RouteStop::create(['route_id' => $route->id, 'stop_name' => 'A', 'address' => 'HN', 'lat' => 21, 'lng' => 105.7, 'stop_order' => 1]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRole::Driver])->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->subDay(), 'arrive_at' => now()->subDay()->addHours(2),
        'available_seats' => 9, 'price' => $amount, 'status' => 'completed',
    ]);

    $mk = function (string $method) use ($trip, $stop, $amount) {
        Booking::create([
            'booking_code' => 'HNHP'.now()->format('ymd').fake()->unique()->numerify('####'),
            'user_id' => User::factory()->create(['role' => UserRole::Customer])->id, 'trip_id' => $trip->id,
            'pickup_stop_id' => $stop->id, 'dropoff_stop_id' => $stop->id, 'passenger_count' => 1,
            'contact_name' => 'A', 'contact_phone' => '0900000000', 'subtotal' => $amount, 'final_amount' => $amount,
            'payment_method' => $method, 'payment_status' => 'paid', 'booking_status' => 'completed', 'qr_token' => Str::random(32),
        ]);
    };
    for ($i = 0; $i < $online; $i++) {
        $mk('momo');
    }
    for ($i = 0; $i < $cash; $i++) {
        $mk('cash');
    }

    return $operator;
}
