<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'operator_id',
        'current_vehicle_id',
        'license_number',
        'license_class',
        'license_expiry',
        'id_card_number',
        'id_card_front_url',
        'id_card_back_url',
        'license_front_url',
        'rating_avg',
        'total_trips',
        'is_online',
        'current_lat',
        'current_lng',
        'location_updated_at',
        'status',
        'verified_at',
    ];

    protected $hidden = [
        'id_card_number',
        'id_card_front_url',
        'id_card_back_url',
    ];

    protected function casts(): array
    {
        return [
            'status'              => DriverStatus::class,
            'license_expiry'      => 'date',
            'verified_at'         => 'datetime',
            'location_updated_at' => 'datetime',
            'is_online'           => 'boolean',
            'rating_avg'          => 'decimal:2',
            'current_lat'         => 'decimal:8',
            'current_lng'         => 'decimal:8',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'current_vehicle_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', DriverStatus::Pending);
    }

    public function scopeVerified($query)
    {
        return $query->where('status', DriverStatus::Verified);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeForOperator($query, string $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isVerified(): bool
    {
        return $this->status === DriverStatus::Verified;
    }

    public function hasLicenseExpired(): bool
    {
        return $this->license_expiry->isPast();
    }

    public function isLocationStale(): bool
    {
        return !$this->location_updated_at || $this->location_updated_at->diffInSeconds() > 30;
    }
}
