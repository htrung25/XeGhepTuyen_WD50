<?php

use App\Enums\UserRoleEnum;
use App\Models\AdminRole;
use App\Models\Payment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Admin xác nhận thủ công đã nhận tiền qua QR MoMo (initiateMomo() không còn gọi
 * captureWallet — xem PaymentService::initiateMomo()/confirmManualPayment()).
 * Tái dùng makePendingMomoPayment() từ MomoCallbackTest.php + superAdminRole()
 * (tests/Pest.php).
 */
function actingAsAdminWithPermissions(array $permissions): User
{
    $role = AdminRole::create([
        'name' => 'Vai trò test',
        'slug' => 'vai-tro-test-'.uniqid(),
        'permissions' => $permissions,
        'is_super' => false,
    ]);
    $admin = User::factory()->create(['role' => UserRoleEnum::Admin, 'admin_role_id' => $role->id]);
    Sanctum::actingAs($admin);

    return $admin;
}

it('cho phép admin xác nhận thủ công đã nhận tiền qua QR MoMo', function () {
    $payment = makePendingMomoPayment();
    $booking = $payment->booking;
    $admin = actingAsAdminWithPermissions(['finance.confirm_payment']);

    $this->postJson("/api/admin/finance/payments/{$payment->id}/confirm")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($payment->fresh()->status->value)->toBe('success');
    expect($booking->fresh()->payment_status->value)->toBe('paid');
    expect($booking->fresh()->booking_status->value)->toBe('confirmed');
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'confirm_manual_payment',
        'model_type' => Payment::class,
        'model_id' => $payment->id,
    ]);
});

it('chặn xác nhận thủ công cho phương thức không phải MoMo', function () {
    $payment = makePendingMomoPayment();
    $payment->update(['method' => 'vnpay']);
    actingAsAdminWithPermissions(['finance.confirm_payment']);

    $this->postJson("/api/admin/finance/payments/{$payment->id}/confirm")
        ->assertStatus(422)
        ->assertJsonPath('code', 'METHOD_NOT_SUPPORTED');

    expect($payment->fresh()->status->value)->toBe('pending');
});

it('chặn xác nhận thủ công giao dịch đã xử lý xong', function () {
    $payment = makePendingMomoPayment();
    $payment->update(['status' => 'success', 'paid_at' => now()]);
    actingAsAdminWithPermissions(['finance.confirm_payment']);

    $this->postJson("/api/admin/finance/payments/{$payment->id}/confirm")
        ->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_NOT_PENDING');
});

it('chặn xác nhận thủ công khi vé đã hết hạn/không còn chờ thanh toán', function () {
    $payment = makePendingMomoPayment();
    $payment->booking->update([
        'booking_status' => 'cancelled',
        'cancelled_at' => now(),
        'cancel_reason' => 'Hết hạn thanh toán',
        'expires_at' => now()->subMinute(),
    ]);
    actingAsAdminWithPermissions(['finance.confirm_payment']);

    $this->postJson("/api/admin/finance/payments/{$payment->id}/confirm")
        ->assertStatus(422)
        ->assertJsonPath('code', 'PAYMENT_VERIFICATION_FAILED');

    expect($payment->fresh()->status->value)->toBe('pending');
});

it('trả 404 khi giao dịch không tồn tại', function () {
    actingAsAdminWithPermissions(['finance.confirm_payment']);

    $this->postJson('/api/admin/finance/payments/khong-ton-tai/confirm')
        ->assertStatus(404)
        ->assertJsonPath('code', 'PAYMENT_NOT_FOUND');
});

it('chặn admin không có quyền finance.confirm_payment', function () {
    $payment = makePendingMomoPayment();
    actingAsAdminWithPermissions(['finance.view']);

    $this->postJson("/api/admin/finance/payments/{$payment->id}/confirm")
        ->assertStatus(403);

    expect($payment->fresh()->status->value)->toBe('pending');
});
