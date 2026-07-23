<?php

namespace App\Models;

use App\Enums\WalletTransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
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
            'balance' => 'integer',
            'pending_balance' => 'integer',
            'updated_at' => 'datetime',
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
    /**
     * Cộng tiền vào ví (nạp tiền / hoàn tiền).
     *
     * @param  string|null  $idempotencyKey  Khóa nghiệp vụ tất định. Truyền key ⇒ gọi lại
     *                                       nhiều lần chỉ ghi MỘT giao dịch (queue redis là
     *                                       at-least-once). null ⇒ hành vi cũ, không chống trùng.
     */
    public function credit(int $amount, string $description, WalletTransactionTypeEnum $type, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type, $bookingId, $idempotencyKey) {
            // Khóa hàng ví: (1) serialize các giao dịch cùng ví nên kiểm key bên dưới là
            // airtight, (2) giữ balance_after nhất quán — không bị giao dịch song song chen
            // giữa increment và refresh làm số dư ghi vào sổ bị nhảy.
            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing; // no-op: đã xử lý ở lần giao trước
                }
            }

            $wallet->increment('balance', $amount);
            $wallet->refresh();
            $this->balance = $wallet->balance; // đồng bộ instance mà caller đang giữ

            return $wallet->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'booking_id' => $bookingId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    /**
     * Trừ tiền từ ví (thanh toán / rút tiền)
     *
     * @throws InsufficientBalanceException
     */
    /**
     * Trừ tiền từ ví (thanh toán / rút tiền).
     *
     * @param  string|null  $idempotencyKey  Xem credit(). Kiểm key TRƯỚC kiểm số dư —
     *                                       lần giao trùng không được coi là "thiếu tiền".
     *
     * @throws InsufficientBalanceException
     */
    public function debit(int $amount, string $description, WalletTransactionTypeEnum $type, ?string $bookingId = null, ?string $idempotencyKey = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $type, $bookingId, $idempotencyKey) {
            $wallet = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            if ($wallet->balance < $amount) {
                throw new InsufficientBalanceException(
                    "Số dư ví không đủ. Cần {$amount}đ, hiện có {$wallet->balance}đ"
                );
            }

            $wallet->decrement('balance', $amount);
            $wallet->refresh();
            $this->balance = $wallet->balance;

            return $wallet->transactions()->create([
                'type' => $type,
                'amount' => -$amount,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'booking_id' => $bookingId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 0, ',', '.').'đ';
    }
}
