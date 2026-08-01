<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\BookingService;
use App\Services\VoucherService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

function voucherLifecycleContext(?Operator $operator = null): array
{
    $operator ??= Operator::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Operator])->id,
        'company_name' => 'NX Voucher '.Str::random(4),
        'business_license' => 'GP-'.Str::random(8),
        'status' => 'verified',
    ]);
    $route = Route::create([
        'operator_id' => $operator->id,
        'name' => 'HN - HP '.Str::random(4),
        'base_price' => 150000,
    ]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'),
        'license_class' => 'B2', 'license_expiry' => now()->addYear(),
        'id_card_number' => fake()->unique()->numerify('############'), 'status' => 'verified',
    ]);
    $trip = Trip::create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addDays(2), 'arrive_at' => now()->addDays(2)->addHours(2),
        'available_seats' => 8, 'price' => 150000, 'status' => 'scheduled',
    ]);

    return [$operator, $trip];
}

function voucherFor(?string $operatorId = null, int $usedCount = 0): Voucher
{
    return Voucher::create([
        'code' => 'VC'.strtoupper(Str::random(8)),
        'operator_id' => $operatorId,
        'discount_type' => 'fixed',
        'discount_value' => 20000,
        'min_order' => 100000,
        'max_discount' => null,
        'usage_limit' => 10,
        'used_count' => $usedCount,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addMonth(),
        'is_active' => true,
    ]);
}

function pendingBookingUsingVoucher(Trip $trip, User $customer, Voucher $voucher): Booking
{
    $booking = Booking::create([
        'booking_code' => 'VC'.strtoupper(Str::random(10)),
        'user_id' => $customer->id,
        'trip_id' => $trip->id,
        'passenger_count' => 1,
        'contact_name' => 'Khách voucher',
        'contact_phone' => '0900000000',
        'subtotal' => 150000,
        'discount_amount' => 20000,
        'final_amount' => 130000,
        'payment_method' => 'momo',
        'payment_status' => 'unpaid',
        'booking_status' => 'pending',
        'voucher_id' => $voucher->id,
        'qr_token' => Str::random(32),
        'expires_at' => now()->addMinutes(15),
    ]);
    VoucherUsage::create([
        'voucher_id' => $voucher->id,
        'booking_id' => $booking->id,
        'user_id' => $customer->id,
        'discount_applied' => 20000,
    ]);

    return $booking;
}

it('không cho dùng voucher riêng của nhà xe khác', function () {
    [$operatorA, $tripA] = voucherLifecycleContext();
    [$operatorB] = voucherLifecycleContext();
    $voucher = voucherFor($operatorB->id);
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);

    expect(fn () => app(VoucherService::class)->validate($voucher->code, 150000, $customer, $tripA))
        ->toThrow(InvalidArgumentException::class, 'không áp dụng');
});

it('hủy booking chưa thanh toán trả lại lượt voucher đúng một lần', function () {
    [, $trip] = voucherLifecycleContext();
    $voucher = voucherFor(usedCount: 1);
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $booking = pendingBookingUsingVoucher($trip, $customer, $voucher);

    app(BookingService::class)->cancel($booking, $customer, 'Đổi kế hoạch');

    expect($voucher->fresh()->used_count)->toBe(0)
        ->and(VoucherUsage::where('booking_id', $booking->id)->exists())->toBeFalse()
        ->and($trip->fresh()->available_seats)->toBe(9);

    expect(fn () => app(BookingService::class)->cancel($booking->fresh(), $customer, 'retry'))
        ->toThrow(InvalidArgumentException::class);
    expect($trip->fresh()->available_seats)->toBe(9);
});

it('booking hết hạn thanh toán trả lại lượt voucher', function () {
    [, $trip] = voucherLifecycleContext();
    $voucher = voucherFor(usedCount: 1);
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);
    $booking = pendingBookingUsingVoucher($trip, $customer, $voucher);
    $booking->update(['expires_at' => now()->subMinute()]);

    app(BookingService::class)->expire($booking->fresh());

    expect($voucher->fresh()->used_count)->toBe(0)
        ->and(VoucherUsage::where('booking_id', $booking->id)->exists())->toBeFalse();
});

it('database chặn một khách dùng cùng voucher cho booking thứ hai', function () {
    [, $trip] = voucherLifecycleContext();
    $voucher = voucherFor(usedCount: 1);
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);
    pendingBookingUsingVoucher($trip, $customer, $voucher);
    $otherBooking = Booking::create([
        'booking_code' => 'VC'.strtoupper(Str::random(10)), 'user_id' => $customer->id,
        'trip_id' => $trip->id, 'passenger_count' => 1, 'contact_name' => 'Khách',
        'contact_phone' => '0900000000', 'subtotal' => 150000, 'final_amount' => 150000,
        'payment_method' => 'momo', 'payment_status' => 'unpaid', 'booking_status' => 'pending',
        'qr_token' => Str::random(32),
    ]);

    expect(fn () => VoucherUsage::create([
        'voucher_id' => $voucher->id, 'booking_id' => $otherBooking->id,
        'user_id' => $customer->id, 'discount_applied' => 20000,
    ]))->toThrow(QueryException::class);
});
