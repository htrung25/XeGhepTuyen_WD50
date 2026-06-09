<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherUsage extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'voucher_id',
        'booking_id',
        'user_id',
        'discount_applied',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_applied' => 'integer',
            'used_at'          => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
