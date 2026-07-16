<?php

use App\Enums\UserRole;
use App\Models\AdminRole;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Hoàn tiền thủ công admin (PRD F-A03). Tái dùng makeOperatorWithRevenue() + superAdminRole()
 * (tests/Pest.php). makeOperatorWithRevenue chỉ tạo booking paid (chưa có Payment) nên test
 * tự bổ sung Payment success để PaymentService::refund xử lý được.
 */
function refundTestBooking(): Booking
{
    makeOperatorWithRevenue(online: 1, cash: 0); // 1 vé momo, paid, 150.000đ
    $booking = Booking::firstOrFail();

    Payment::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'amount' => $booking->final_amount,
        'method' => 'momo',
        'status' => 'success',
        'paid_at' => now(),
    ]);

    return $booking->fresh();
}

function actingAsSuperAdmin(): User
{
    $admin = User::factory()->create(['role' => UserRole::Admin, 'admin_role_id' => superAdminRole()->id]);
    Sanctum::actingAs($admin);

    return $admin;
}

it('cho phép admin hoàn tiền thủ công vé đã thanh toán', function () {
    $booking = refundTestBooking();
    $admin = actingAsSuperAdmin();

    $this->postJson("/api/admin/finance/refund/{$booking->id}", [
        'amount' => 150000,
        'reason' => 'Khách khiếu nại chất lượng dịch vụ',
    ])
        ->assertOk()
        ->assertJsonPath('data.amount', 150000);

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'status' => 'refunded',
        'refund_amount' => 150000,
    ]);
    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'payment_status' => 'refunded',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'refund_booking',
        'model_type' => Booking::class,
        'model_id' => $booking->id,
    ]);
});

it('chặn hoàn tiền vượt quá giá trị vé', function () {
    $booking = refundTestBooking();
    actingAsSuperAdmin();

    $this->postJson("/api/admin/finance/refund/{$booking->id}", [
        'amount' => 200000,
        'reason' => 'Quá tay',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'REFUND_EXCEEDS_TOTAL');

    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'payment_status' => 'paid']);
});

it('chặn hoàn tiền vé chưa thanh toán', function () {
    $booking = refundTestBooking();
    $booking->update(['payment_status' => 'unpaid']);
    actingAsSuperAdmin();

    $this->postJson("/api/admin/finance/refund/{$booking->id}", [
        'amount' => 100000,
        'reason' => 'Test',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'BOOKING_NOT_PAID');
});

it('yêu cầu nhập số tiền và lý do', function () {
    $booking = refundTestBooking();
    actingAsSuperAdmin();

    $this->postJson("/api/admin/finance/refund/{$booking->id}", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['amount', 'reason']);
});

it('chặn admin không có quyền finance.refund', function () {
    $booking = refundTestBooking();

    $role = AdminRole::create([
        'name' => 'Kế toán xem',
        'slug' => 'ketoan-xem',
        'permissions' => ['finance.view'], // KHÔNG có finance.refund
        'is_super' => false,
    ]);
    Sanctum::actingAs(User::factory()->create(['role' => UserRole::Admin, 'admin_role_id' => $role->id]));

    $this->postJson("/api/admin/finance/refund/{$booking->id}", [
        'amount' => 100000,
        'reason' => 'Test',
    ])->assertStatus(403);
});
