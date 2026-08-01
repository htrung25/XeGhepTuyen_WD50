<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'user_id',
        'amount',
        'method',
        'status',
        'gateway_txn_id',
        'gateway_order_id',
        'gateway_response',
        'refund_amount',
        'refunded_at',
        'paid_at',
        'collected_by',
    ];

    protected $hidden = ['gateway_response'];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethodEnum::class,
            'status' => PaymentStatusEnum::class,
            'gateway_response' => 'array',
            'amount' => 'integer',
            'refund_amount' => 'integer',
            'refunded_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatusEnum::Success;
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.').'đ';
    }
}
