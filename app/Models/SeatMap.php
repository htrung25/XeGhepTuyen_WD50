<?php

namespace App\Models;

use App\Enums\SeatStatus;
use App\Enums\SeatType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SeatMap extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'trip_id',
        'seat_code',
        'seat_type',
        'price',
        'status',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'seat_type' => SeatType::class,
            'status'    => SeatStatus::class,
            'locked_at' => 'datetime',
            'price'     => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function passenger(): HasOne
    {
        return $this->hasOne(BookingPassenger::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', SeatStatus::Available);
    }

    public function scopeForTrip(Builder $query, string $tripId): Builder
    {
        return $query->where('trip_id', $tripId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === SeatStatus::Available;
    }

    public function isLockExpired(): bool
    {
        return $this->status === SeatStatus::Locked
            && $this->locked_at
            && $this->locked_at->diffInMinutes() >= 10;
    }
}
