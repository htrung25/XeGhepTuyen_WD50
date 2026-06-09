<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasUuids;

    public $timestamps = false;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance'         => 'integer',
            'pending_balance' => 'integer',
            'updated_at'      => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('created_at');
    }

    // ─── Business Methods ─────────────────────────────────────────────────────

    /**
     * Cộng tiền vào ví (nạp tiền / hoàn tiền)
     */
    public function credit(int $amount, string $description, WalletTransactionType $type, ?string $bookingId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type, $bookingId) {
            $this->increment('balance', $amount);
            $this->refresh();

            return $this->transactions()->create([
                'type'          => $type,
                'amount'        => $amount,
                'balance_after' => $this->balance,
                'description'   => $description,
                'booking_id'    => $bookingId,
            ]);
        });
    }

    /**
     * Trừ tiền từ ví (thanh toán / rút tiền)
     *
     * @throws \App\Exceptions\InsufficientBalanceException
     */
    public function debit(int $amount, string $description, WalletTransactionType $type, ?string $bookingId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type, $bookingId) {
            if ($this->balance < $amount) {
                throw new \App\Exceptions\InsufficientBalanceException(
                    "Số dư ví không đủ. Cần {$amount}đ, hiện có {$this->balance}đ"
                );
            }

            $this->decrement('balance', $amount);
            $this->refresh();

            return $this->transactions()->create([
                'type'          => $type,
                'amount'        => -$amount,
                'balance_after' => $this->balance,
                'description'   => $description,
                'booking_id'    => $bookingId,
            ]);
        });
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 0, ',', '.') . 'đ';
    }
}
