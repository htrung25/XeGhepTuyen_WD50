<?php

use App\Enums\UserRole;
use App\Events\PaymentProcessed;
use App\Exceptions\PaymentVerificationException;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Regression P1: callback MoMo phải đọc resultCode — giao dịch thất bại
 * (resultCode != 0) KHÔNG được đánh Success / confirm vé.
 */
beforeEach(function () {
    config([
        'services.momo.secret_key' => 'test-secret',
        'services.momo.access_key' => 'test-access',
    ]);
});

function makePendingMomoPayment(): Payment
{
    $opUser = User::factory()->create(['role' => UserRole::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP1', 'status' => 'verified',
    ]);
    $route = Route::create([
        'operator_id' => $operator->id, 'name' => 'HN→HP', 'base_price' => 150000,
    ]);
    $pickup = RouteStop::create([
        'route_id' => $route->id, 'stop_name' => 'Mỹ Đình', 'address' => 'Hà Nội',
        'lat' => 21.0, 'lng' => 105.7, 'stop_order' => 1,
    ]);
    $dropoff = RouteStop::create([
        'route_id' => $route->id, 'stop_name' => 'Lạch Tray', 'address' => 'Hải Phòng',
        'lat' => 20.8, 'lng' => 106.6, 'stop_order' => 2,
    ]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $drvUser = User::factory()->create(['role' => UserRole::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'),
        'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addDay(), 'arrive_at' => now()->addDay()->addHours(2),
        'available_seats' => 9, 'price' => 150000, 'status' => 'scheduled',
    ]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $booking = Booking::create([
        'booking_code' => 'HNHP'.now()->format('ymd').'001', 'user_id' => $customer->id, 'trip_id' => $trip->id,
        'pickup_stop_id' => $pickup->id, 'dropoff_stop_id' => $dropoff->id, 'passenger_count' => 1,
        'contact_name' => 'A', 'contact_phone' => '0900000000', 'subtotal' => 150000, 'final_amount' => 150000,
        'payment_method' => 'momo', 'payment_status' => 'unpaid', 'booking_status' => 'pending',
        'qr_token' => Str::random(32),
    ]);

    return Payment::create([
        'booking_id' => $booking->id, 'user_id' => $customer->id, 'amount' => 150000,
        'method' => 'momo', 'status' => 'pending', 'gateway_order_id' => 'XEGHEP-'.strtoupper(Str::random(8)),
    ]);
}

function momoPayload(string $orderId, int $resultCode): array
{
    $p = [
        'partnerCode' => 'MOMO', 'orderId' => $orderId, 'requestId' => $orderId,
        'amount' => '150000', 'orderInfo' => 'Vé xe', 'orderType' => 'momo_wallet',
        'transId' => '999', 'resultCode' => $resultCode, 'message' => 'ok',
        'payType' => 'qr', 'responseTime' => '123', 'extraData' => '',
    ];
    $raw = "accessKey=test-access&amount={$p['amount']}&extraData={$p['extraData']}&message={$p['message']}"
        ."&orderId={$p['orderId']}&orderInfo={$p['orderInfo']}&orderType={$p['orderType']}"
        ."&partnerCode={$p['partnerCode']}&payType={$p['payType']}&requestId={$p['requestId']}"
        ."&responseTime={$p['responseTime']}&resultCode={$p['resultCode']}&transId={$p['transId']}";
    $p['signature'] = hash_hmac('sha256', $raw, 'test-secret');

    return $p;
}

it('KHÔNG confirm vé khi MoMo resultCode != 0 (giao dịch thất bại)', function () {
    $payment = makePendingMomoPayment();

    $ok = app(PaymentService::class)->handleMomoCallback(
        momoPayload($payment->gateway_order_id, 1)
    );

    expect($ok)->toBeFalse();
    expect($payment->fresh()->status->value)->toBe('failed');
    expect($payment->booking->fresh()->booking_status->value)->toBe('pending');
    expect($payment->booking->fresh()->payment_status->value)->toBe('unpaid');
});

it('confirm vé khi MoMo resultCode == 0 (thành công)', function () {
    Event::fake([PaymentProcessed::class]);
    $payment = makePendingMomoPayment();

    $ok = app(PaymentService::class)->handleMomoCallback(
        momoPayload($payment->gateway_order_id, 0)
    );

    expect($ok)->toBeTrue();
    expect($payment->fresh()->status->value)->toBe('success');
    expect($payment->booking->fresh()->booking_status->value)->toBe('confirmed');
    expect($payment->booking->fresh()->payment_status->value)->toBe('paid');
    Event::assertDispatched(PaymentProcessed::class);
});

it('từ chối callback MoMo sai chữ ký', function () {
    $payment = makePendingMomoPayment();
    $payload = momoPayload($payment->gateway_order_id, 0);
    $payload['signature'] = 'tampered';

    expect(fn () => app(PaymentService::class)->handleMomoCallback($payload))
        ->toThrow(PaymentVerificationException::class);
});
