<?php

namespace App\Models;

use App\Enums\SeatStatusEnum;
use App\Enums\SeatTypeEnum;
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
            'seat_type' => SeatTypeEnum::class,
            'status' => SeatStatusEnum::class,
            'locked_at' => 'datetime',
            'price' => 'integer',
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
        return $query->where('status', SeatStatusEnum::Available);
    }

    public function scopeForTrip(Builder $query, string $tripId): Builder
    {
        return $query->where('trip_id', $tripId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        // Ghế Locked nhưng đã quá hạn giữ (10') coi như còn trống — tự liền ngay ở
        // tầng đọc, không phụ thuộc ExpireLockedSeatsJob chạy đúng giờ.
        return $this->status === SeatStatusEnum::Available || $this->isLockExpired();
    }

    public function isLockExpired(): bool
    {
        return $this->status === SeatStatusEnum::Locked
            && $this->locked_at
            && $this->locked_at->diffInMinutes() >= 10;
    }
}
