<?php

use App\Enums\UserRoleEnum;
use App\Enums\WalletTransactionTypeEnum;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function makeWalletUser(): User
{
    return User::factory()->create(['role' => UserRoleEnum::Customer]);
}

it('bảng wallet_transactions có cột idempotency_key', function () {
    expect(Schema::hasColumn('wallet_transactions', 'idempotency_key'))->toBeTrue();
});

it('idempotency_key nhận NULL nhiều lần (dòng lịch sử không vướng UNIQUE)', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    WalletTransaction::create([
        'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => 1000,
        'balance_after' => 1000, 'description' => 'a', 'idempotency_key' => null,
    ]);
    WalletTransaction::create([
        'wallet_id' => $wallet->id, 'type' => 'refund', 'amount' => 2000,
        'balance_after' => 3000, 'description' => 'b', 'idempotency_key' => null,
    ]);

    expect(WalletTransaction::count())->toBe(2);
});

// ─── Task 2: khóa hàng ví + idempotency key ──────────────────────────────────

it('credit() cùng idempotency_key hai lần chỉ cộng tiền MỘT lần', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $first = $wallet->credit(150000, 'Hoàn tiền vé X', WalletTransactionTypeEnum::Refund, null, 'refund:booking-1');
    $second = $wallet->credit(150000, 'Hoàn tiền vé X', WalletTransactionTypeEnum::Refund, null, 'refund:booking-1');

    expect($second->id)->toBe($first->id)                 // trả đúng giao dịch cũ
        ->and(WalletTransaction::count())->toBe(1)
        ->and($wallet->refresh()->balance)->toBe(150000); // KHÔNG phải 300000
});

it('credit() với key khác nhau vẫn ghi đủ (refund + compensation cùng booking)', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $wallet->credit(150000, 'Hoàn tiền', WalletTransactionTypeEnum::Refund, 'booking-1', 'refund:booking-1');
    $wallet->credit(20000, 'Bồi thường', WalletTransactionTypeEnum::Refund, 'booking-1', 'compensation:booking-1');

    expect(WalletTransaction::count())->toBe(2)
        ->and($wallet->refresh()->balance)->toBe(170000);
});

it('credit() không truyền key thì giữ hành vi cũ (ghi mọi lần)', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $wallet->credit(1000, 'x', WalletTransactionTypeEnum::Refund);
    $wallet->credit(1000, 'x', WalletTransactionTypeEnum::Refund);

    expect(WalletTransaction::count())->toBe(2)
        ->and($wallet->refresh()->balance)->toBe(2000);
});

it('debit() cùng idempotency_key hai lần chỉ trừ tiền MỘT lần', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 500000]);

    $first = $wallet->debit(150000, 'Thanh toán vé X', WalletTransactionTypeEnum::Payment, null, 'wallet_payment:booking-1');
    $second = $wallet->debit(150000, 'Thanh toán vé X', WalletTransactionTypeEnum::Payment, null, 'wallet_payment:booking-1');

    expect($second->id)->toBe($first->id)
        ->and(WalletTransaction::count())->toBe(1)
        ->and($wallet->refresh()->balance)->toBe(350000); // KHÔNG phải 200000
});

// ─── Task 3: key tất định tại các điểm gọi ───────────────────────────────────

it('refund() gọi lại nhiều lần chỉ ghi MỘT giao dịch ví (key refund:{booking})', function () {
    $booking = makePaidCancellableBooking(); // helper ở RefundDispatchAfterCommitTest
    Payment::create([
        'booking_id' => $booking->id, 'user_id' => $booking->user_id,
        'amount' => 150000, 'method' => 'momo', 'status' => 'success',
        'gateway_order_id' => 'XEGHEP-'.Str::upper(Str::random(10)), 'paid_at' => now(),
    ]);

    $service = app(PaymentService::class);
    $service->refund($booking, 150000);
    $service->refund($booking->refresh(), 150000);
    $service->refund($booking->refresh(), 150000);

    expect(WalletTransaction::where('idempotency_key', "refund:{$booking->id}")->count())->toBe(1)
        ->and(app(WalletService::class)->getBalance($booking->user))->toBe(150000);
});

it('hoàn tiền và bồi thường cùng booking đều ghi được (key khác nhau)', function () {
    $user = makeWalletUser();
    $service = app(WalletService::class);

    $service->credit($user, 150000, 'Hoàn tiền', 'booking-9', 'refund:booking-9');
    $service->credit($user, 20000, 'Bồi thường', 'booking-9', 'compensation:booking-9');

    expect(WalletTransaction::count())->toBe(2)
        ->and($service->getBalance($user))->toBe(170000);
});

it('balance_after khớp số dư thật sau chuỗi giao dịch', function () {
    $wallet = Wallet::create(['user_id' => makeWalletUser()->id, 'balance' => 0]);

    $wallet->credit(100000, 'a', WalletTransactionTypeEnum::Refund, null, 'k1');
    $wallet->credit(50000, 'b', WalletTransactionTypeEnum::Refund, null, 'k2');
    $last = $wallet->debit(30000, 'c', WalletTransactionTypeEnum::Payment, null, 'k3');

    expect($last->balance_after)->toBe(120000)
        ->and($wallet->refresh()->balance)->toBe(120000);
});
