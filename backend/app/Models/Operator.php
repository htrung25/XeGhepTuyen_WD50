<?php

namespace App\Models;

use App\Enums\OperatorStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operator extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'business_license',
        'tax_code',
        'bank_account',
        'bank_name',
        'bank_account_name',
        'commission_rate',
        'logo_url',
        'description',
        'license_url',
        'status',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => OperatorStatus::class,
            'verified_at' => 'datetime',
            'commission_rate' => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Đơn đăng ký đối tác đã được duyệt để tạo ra nhà xe này (nếu có).
     */
    public function partnerApplication(): HasOne
    {
        return $this->hasOne(PartnerApplication::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', OperatorStatus::Pending);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', OperatorStatus::Verified);
    }

    public function scopeActive($query)
    {
        return $query->where('status', OperatorStatus::Verified);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->status === OperatorStatus::Verified;
    }
}
