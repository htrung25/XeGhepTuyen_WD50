<?php

use App\Enums\UserRoleEnum;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Trang kết quả sau khi MoMo chuyển hướng về chỉ còn orderId trên URL (state phía
 * client đã mất khi rời khỏi SPA), nên cần tra cứu vé theo mã giao dịch — và chỉ
 * chủ vé mới được xem.
 */
function bookingWithPayment(): array
{
    makeOperatorWithRevenue(online: 1, cash: 0);
    $booking = Booking::firstOrFail();

    $payment = Payment::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'amount' => (int) $booking->final_amount,
        'method' => 'momo',
        'status' => 'pending',
        'gateway_order_id' => 'XEGHEP-'.strtoupper(Str::random(10)),
    ]);

    return [$booking, $payment];
}

it('trả về vé tương ứng với mã giao dịch cho chủ vé', function () {
    [$booking, $payment] = bookingWithPayment();
    $owner = User::findOrFail($booking->user_id);
    Sanctum::actingAs($owner, ['*'], 'sanctum');
    Sanctum::actingAs($owner, ['*'], 'customer');

    $this->getJson("/api/customer/payments/by-order/{$payment->gateway_order_id}")
        ->assertOk()
        ->assertJsonPath('data.booking_id', $booking->id)
        ->assertJsonPath('data.booking_code', $booking->booking_code)
        ->assertJsonPath('data.amount', (int) $booking->final_amount)
        ->assertJsonStructure(['data' => ['booking_status', 'payment_status']]);
});

it('chặn khách khác xem giao dịch không phải của mình', function () {
    [, $payment] = bookingWithPayment();
    $other = User::factory()->create(['role' => UserRoleEnum::Customer]);
    Sanctum::actingAs($other, ['*'], 'sanctum');
    Sanctum::actingAs($other, ['*'], 'customer');

    $this->getJson("/api/customer/payments/by-order/{$payment->gateway_order_id}")
        ->assertStatus(403);
});

it('trả 404 khi mã giao dịch không tồn tại', function () {
    [$booking] = bookingWithPayment();
    $owner = User::findOrFail($booking->user_id);
    Sanctum::actingAs($owner, ['*'], 'sanctum');
    Sanctum::actingAs($owner, ['*'], 'customer');

    $this->getJson('/api/customer/payments/by-order/KHONG-CO-THAT')
        ->assertStatus(404);
});
