<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'operator_id',
        'discount_type',
        'discount_value',
        'min_order',
        'max_discount',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_type'  => DiscountType::class,
            'discount_value' => 'decimal:2',
            'min_order'      => 'integer',
            'max_discount'   => 'integer',
            'usage_limit'    => 'integer',
            'used_count'     => 'integer',
            'valid_from'     => 'datetime',
            'valid_until'    => 'datetime',
            'is_active'      => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where('valid_from', '<=', now())
                     ->where('valid_until', '>=', now())
                     ->whereColumn('used_count', '<', 'usage_limit');
    }

    // ─── Business Methods ─────────────────────────────────────────────────────

    public function isValid(): bool
    {
        return $this->is_active
            && now()->between($this->valid_from, $this->valid_until)
            && $this->used_count < $this->usage_limit;
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($subtotal < $this->min_order) {
            return 0;
        }

        if ($this->discount_type === DiscountType::Percent) {
            $discount = (int) ($subtotal * $this->discount_value / 100);
            return $this->max_discount ? min($discount, $this->max_discount) : $discount;
        }

        return (int) min($this->discount_value, $subtotal);
    }
}
