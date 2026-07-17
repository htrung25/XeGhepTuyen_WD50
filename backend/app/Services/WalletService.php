<?php

namespace App\Services;

use App\Enums\WalletTransactionTypeEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

class WalletService
{
    public function getOrCreate(User $user): Wallet
    {
        return $user->wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);
    }

    public function credit(User $user, int $amount, string $description, ?string $bookingId = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($user);

        return $wallet->credit($amount, $description, WalletTransactionTypeEnum::Refund, $bookingId);
    }

    public function debit(User $user, int $amount, string $description, ?string $bookingId = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($user);

        return $wallet->debit($amount, $description, WalletTransactionTypeEnum::Payment, $bookingId);
    }

    public function getBalance(User $user): int
    {
        return $this->getOrCreate($user)->balance;
    }

    public function getTransactions(User $user, int $perPage = 20)
    {
        return $this->getOrCreate($user)
            ->transactions()
            ->paginate($perPage);
    }
}
