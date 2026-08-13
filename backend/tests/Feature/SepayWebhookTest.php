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
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    config([
        'services.sepay.webhook_token' => 'test-sepay-token',
        'services.sepay.bank_acc' => '0935555555',
        'services.sepay.bank_name' => 'MBBank',
    ]);
});

function setupSepayTestContext(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'SePay Express', 'business_license' => 'GP-8888', 'status' => 'verified',
    ]);

    $route = Route::create(['operator_id' => $operator->id, 'name' => 'Hà Nội - Hải Phòng', 'base_price' => 150000]);

    RouteStop::create(['route_id' => $route->id, 'stop_name' => 'Bến xe Mỹ Đình', 'address' => 'Mỹ Đình, Hà Nội', 'lat' => 21.0285, 'lng' => 105.8544, 'stop_order' => 1]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-88888',
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);

    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-888888', 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => '888888888888', 'status' => 'verified',
    ]);

    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addHours(2), 'arrive_at' => now()->addHours(4),
        'available_seats' => 9, 'price' => 150000, 'status' => 'scheduled',
    ]);

    $seat = SeatMap::create([
        'trip_id' => $trip->id,
        'seat_code' => 'A2',
        'price' => 150000,
        'status' => 'available',
    ]);

    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);

    return [$trip, $seat, $customer];
}

it('initiates SePay VietQR payment info and processes callback webhook successfully', function () {
    [$trip, $seat, $customer] = setupSepayTestContext();

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    // 1. Create booking
    $booking = Booking::create([
        'booking_code' => 'XGBOOK'.rand(1000, 9999),
        'user_id' => $customer->id,
        'trip_id' => $trip->id,
        'pickup_address' => 'Mỹ Đình, Hà Nội',
        'pickup_lat' => 21.0285,
        'pickup_lng' => 105.8544,
        'dropoff_address' => 'Lạch Tray, Hải Phòng',
        'dropoff_lat' => 20.8449,
        'dropoff_lng' => 106.6881,
        'passenger_count' => 1,
        'contact_name' => 'Nguyễn Văn SePay',
        'contact_phone' => '0988888888',
        'subtotal' => 150000,
        'final_amount' => 150000,
        'payment_method' => 'vnpay', // Maps to SePay VietQR
        'payment_status' => 'unpaid',
        'booking_status' => 'pending',
        'qr_token' => Str::random(32),
    ]);

    // 2. Initiate payment
    $initResponse = $this->postJson('/api/customer/payments/initiate', [
        'booking_id' => $booking->id,
        'method' => 'vnpay',
    ]);

    $initResponse->assertStatus(200);
    $initResponse->assertJsonPath('success', true);
    $initResponse->assertJsonStructure([
        'data' => [
            'payment_url',
            'order_id',
            'bank_info' => [
                'bank_name',
                'bank_acc',
                'acc_name',
                'amount',
                'code',
            ],
        ],
    ]);

    $gatewayOrderId = $initResponse->json('data.order_id');
    $amount = $initResponse->json('data.bank_info.amount');

    // 3. Simulate SePay webhook callback
    $webhookToken = config('services.sepay.webhook_token');

    $webhookResponse = $this->withHeaders([
        'Authorization' => "Bearer {$webhookToken}",
    ])->postJson('/api/customer/payments/sepay/webhook', [
        'id' => 999999,
        'gateway' => 'MBBank',
        'transactionDate' => '2026-07-08 15:00:00',
        'accountNumber' => '0935555555',
        'transferType' => 'in',
        'transferAmount' => $amount,
        'content' => "Chuyen khoan thanh toan don hang {$gatewayOrderId} tren XeGhep",
        'code' => 'SEPAYTX9999',
    ]);

    $webhookResponse->assertStatus(200);
    $webhookResponse->assertJsonPath('success', true);

    // Verify booking updated to Paid and Confirmed in DB
    $booking->refresh();
    expect($booking->payment_status->value)->toBe('paid');
    expect($booking->booking_status->value)->toBe('confirmed');
});

it('rejects SePay webhook with invalid webhook token', function () {
    $webhookResponse = $this->withHeaders([
        'Authorization' => 'Bearer invalid_token_here',
    ])->postJson('/api/customer/payments/sepay/webhook', [
        'id' => 999999,
        'transferAmount' => 150000,
        'content' => 'XEGHEP-ABC123XYZ',
    ]);

    $webhookResponse->assertStatus(401);
});

it('fails SePay webhook if payment amount is mismatched', function () {
    [$trip, $seat, $customer] = setupSepayTestContext();

    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    // Create booking
    $booking = Booking::create([
        'booking_code' => 'XGBOOK'.rand(1000, 9999),
        'user_id' => $customer->id,
        'trip_id' => $trip->id,
        'pickup_address' => 'Mỹ Đình',
        'pickup_lat' => 21, 'pickup_lng' => 105,
        'dropoff_address' => 'Lạch Tray',
        'dropoff_lat' => 20, 'dropoff_lng' => 106,
        'passenger_count' => 1,
        'contact_name' => 'Nguyễn Văn SePay Mismatch',
        'contact_phone' => '0988888888',
        'subtotal' => 150000,
        'final_amount' => 150000,
        'payment_method' => 'vnpay',
        'payment_status' => 'unpaid',
        'booking_status' => 'pending',
        'qr_token' => Str::random(32),
    ]);

    // Initiate payment
    $initResponse = $this->postJson('/api/customer/payments/initiate', [
        'booking_id' => $booking->id,
        'method' => 'vnpay',
    ]);

    $gatewayOrderId = $initResponse->json('data.order_id');

    // Webhook with insufficient amount (e.g. 50,000 instead of 150,000)
    $webhookToken = config('services.sepay.webhook_token');

    $webhookResponse = $this->withHeaders([
        'Authorization' => "Bearer {$webhookToken}",
    ])->postJson('/api/customer/payments/sepay/webhook', [
        'id' => 888888,
        'transferAmount' => 50000,
        'content' => "Chuyen khoan thanh toan don hang {$gatewayOrderId}",
    ]);

    $webhookResponse->assertStatus(400); // PaymentVerificationException maps to 400
});

it('rejects SePay outgoing transfer even when content and amount match', function () {
    [$trip, $seat, $customer] = setupSepayTestContext();
    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    $booking = Booking::create([
        'booking_code' => 'XGBOOK'.rand(1000, 9999), 'user_id' => $customer->id,
        'trip_id' => $trip->id, 'pickup_address' => 'Mỹ Đình', 'pickup_lat' => 21,
        'pickup_lng' => 105, 'dropoff_address' => 'Lạch Tray', 'dropoff_lat' => 20,
        'dropoff_lng' => 106, 'passenger_count' => 1, 'contact_name' => 'Khách',
        'contact_phone' => '0988888888', 'subtotal' => 150000, 'final_amount' => 150000,
        'payment_method' => 'vnpay', 'payment_status' => 'unpaid',
        'booking_status' => 'pending', 'qr_token' => Str::random(32),
    ]);

    $orderId = $this->postJson('/api/customer/payments/initiate', [
        'booking_id' => $booking->id, 'method' => 'vnpay',
    ])->assertOk()->json('data.order_id');

    $this->withHeaders([
        'Authorization' => 'Bearer '.config('services.sepay.webhook_token'),
    ])->postJson('/api/customer/payments/sepay/webhook', [
        'id' => 777777,
        'gateway' => config('services.sepay.bank_name'),
        'accountNumber' => config('services.sepay.bank_acc'),
        'transferType' => 'out',
        'transferAmount' => 150000,
        'content' => "Thanh toan {$orderId}",
    ])->assertStatus(400);

    expect($booking->fresh()->payment_status->value)->toBe('unpaid');
});

it('rejects SePay webhook sent to a different beneficiary account', function () {
    [$trip, $seat, $customer] = setupSepayTestContext();
    Sanctum::actingAs($customer, ['*'], 'sanctum');
    Sanctum::actingAs($customer, ['*'], 'customer');

    $booking = Booking::create([
        'booking_code' => 'XGBOOK'.rand(1000, 9999), 'user_id' => $customer->id,
        'trip_id' => $trip->id, 'pickup_address' => 'Mỹ Đình', 'pickup_lat' => 21,
        'pickup_lng' => 105, 'dropoff_address' => 'Lạch Tray', 'dropoff_lat' => 20,
        'dropoff_lng' => 106, 'passenger_count' => 1, 'contact_name' => 'Khách',
        'contact_phone' => '0988888888', 'subtotal' => 150000, 'final_amount' => 150000,
        'payment_method' => 'vnpay', 'payment_status' => 'unpaid',
        'booking_status' => 'pending', 'qr_token' => Str::random(32),
    ]);
    $orderId = $this->postJson('/api/customer/payments/initiate', [
        'booking_id' => $booking->id, 'method' => 'vnpay',
    ])->assertOk()->json('data.order_id');

    $this->withHeaders([
        'Authorization' => 'Bearer '.config('services.sepay.webhook_token'),
    ])->postJson('/api/customer/payments/sepay/webhook', [
        'id' => 666666,
        'gateway' => config('services.sepay.bank_name'),
        'accountNumber' => '0000000000',
        'transferType' => 'in',
        'transferAmount' => 150000,
        'content' => "Thanh toan {$orderId}",
    ])->assertStatus(400);

    expect($booking->fresh()->payment_status->value)->toBe('unpaid');
});
