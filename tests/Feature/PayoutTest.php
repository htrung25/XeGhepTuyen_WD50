<?php

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Payout;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * R1/R2/R4: đồng bộ luồng quyết toán operator↔admin (không chi trùng / mồ côi pending)
 * + atomic. settlement = online_net − cash_commission (SettlementService).
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

it('operator gửi yêu cầu quyết toán = settlement; lần 2 hết số dư', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0); // settlement = 135.000
    // RevenueController dùng auth('operator') → cần token thật để cả middleware lẫn guard resolve.
    $headers = ['Authorization' => 'Bearer '.$operator->user->createToken('operator_token')->plainTextToken];

    $this->postJson('/api/operator/revenue/payout-request', [], $headers)
        ->assertCreated()
        ->assertJsonPath('data.amount', 135000);

    expect((int) Payout::where('operator_id', $operator->id)->where('status', 'pending')->sum('amount'))->toBe(135000);

    // Hết số dư khả dụng (đã có yêu cầu pending)
    $this->postJson('/api/operator/revenue/payout-request', [], $headers)->assertStatus(422);
});

it('admin quyết toán gộp yêu cầu pending: 1 paid = outstanding, pending → rejected', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);
    Payout::create(['operator_id' => $operator->id, 'amount' => 135000, 'status' => 'pending', 'requested_at' => now()]);

    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])
        ->assertCreated()
        ->assertJsonPath('data.amount', 135000);

    expect((int) Payout::where('operator_id', $operator->id)->where('status', 'paid')->sum('amount'))->toBe(135000);
    expect(Payout::where('operator_id', $operator->id)->where('status', 'paid')->count())->toBe(1);
    expect(Payout::where('operator_id', $operator->id)->where('status', 'pending')->count())->toBe(0);
    expect(Payout::where('operator_id', $operator->id)->where('status', 'rejected')->count())->toBe(1);
});

it('admin KHÔNG chi khi nhà xe đang NỢ nền tảng (vé tiền mặt)', function () {
    $operator = makeOperatorWithRevenue(online: 0, cash: 1); // settlement = -15.000

    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])
        ->assertStatus(422)
        ->assertJsonPath('code', 'OPERATOR_OWES_PLATFORM');

    expect(Payout::where('operator_id', $operator->id)->where('status', 'paid')->count())->toBe(0);
});

it('admin chi lần 2 báo hết số dư (không chi trùng)', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Sanctum::actingAs($admin);
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])->assertCreated();
    $this->postJson('/api/admin/finance/payouts', ['commission_id' => $operator->id])
        ->assertStatus(422)
        ->assertJsonPath('code', 'NOTHING_TO_SETTLE');

    expect((int) Payout::where('operator_id', $operator->id)->where('status', 'paid')->sum('amount'))->toBe(135000);
});
