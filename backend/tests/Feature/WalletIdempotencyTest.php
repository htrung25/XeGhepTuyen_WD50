<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Schema;

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
