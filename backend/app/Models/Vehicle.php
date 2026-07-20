<?php

namespace App\Models;

use App\Enums\VehicleStatusEnum;
use App\Enums\VehicleTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'operator_id',
        'plate_number',
        'brand',
        'model',
        'color',
        'year',
        'vehicle_type',
        'seat_count',
        'registration_number',
        'registration_expiry',
        'insurance_expiry',
        'image_url',
        'amenities',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleTypeEnum::class,
            'status' => VehicleStatusEnum::class,
            'amenities' => 'array',
            'registration_expiry' => 'date',
            'insurance_expiry' => 'date',
            'seat_count' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function assignedDriver(): HasOne
    {
        return $this->hasOne(Driver::class, 'current_vehicle_id');
    }

    public function activeDriver(): HasOneThrough
    {
        return $this->hasOneThrough(
            Driver::class,
            Trip::class,
            'vehicle_id',
            'id',
            'id',
            'driver_id'
        )->whereIn('trips.status', ['scheduled', 'boarding', 'in_progress'])
            ->latest('trips.depart_at');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VehicleStatusEnum::Active);
    }

    public function scopeForOperator($query, string $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isRegistrationExpiringSoon(): bool
    {
        return $this->registration_expiry && $this->registration_expiry->diffInDays() <= 30;
    }

    public function generateSeatCodes(): array
    {
        return match ($this->vehicle_type) {
            VehicleTypeEnum::Mpv7 => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1'],
            VehicleTypeEnum::Van9 => ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', 'D1', 'D2', 'E1'],
            VehicleTypeEnum::Minibus16 => collect(range('A', 'D'))->flatMap(fn ($r) => ["$r1", "$r2", "$r3", "$r4"])->toArray(),
            default => ['A1', 'A2', 'B1', 'B2'],
        };
    }
}
