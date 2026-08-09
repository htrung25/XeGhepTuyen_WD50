<?php

namespace App\Services;

use App\Enums\WalletTransactionTypeEnum;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WalletService
{
    public function getOrCreate(User $user): Wallet
    {
        // firstOrCreate thay vì `$user->wallet ?? Wallet::create(...)`: quan hệ `wallet`
        // bị CACHE trên instance User, nên sau lần tạo đầu nó vẫn trả null ⇒ lần gọi thứ
        // hai trong cùng request tạo ví lần nữa và vi phạm unique wallets.user_id
        // (đúng kịch bản hoàn tiền + bồi thường cùng một vé).
        return Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
    }

    public function credit(User $user, int $amount, string $description, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($user);

        return $wallet->credit($amount, $description, WalletTransactionTypeEnum::Refund, $bookingId, $idempotencyKey);
    }

    public function debit(User $user, int $amount, string $description, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        $wallet = $this->getOrCreate($user);

        return $wallet->debit($amount, $description, WalletTransactionTypeEnum::Payment, $bookingId, $idempotencyKey);
    }

    public function getBalance(User $user): int
    {
        return $this->getOrCreate($user)->balance;
    }

    public function getTransactions(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->getOrCreate($user)
            ->transactions()
            ->paginate($perPage);
    }
}
