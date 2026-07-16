<?php

namespace App\Models;

use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasUuids;

    protected $fillable = [
        'route_id',
        'vehicle_id',
        'driver_id',
        'depart_at',
        'arrive_at',
        'available_seats',
        'price',
        'note',
        'tracking_code',
        'status',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'depart_at' => 'datetime',
            'arrive_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'available_seats' => 'integer',
            'price' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function seatMaps(): HasMany
    {
        return $this->hasMany(SeatMap::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAvailable(Builder $query, int $passengers = 1): Builder
    {
        return $query->where('status', TripStatus::Scheduled)
            ->where('available_seats', '>=', $passengers)
            ->where('depart_at', '>', now()->addMinutes((int) config('booking.min_lead_minutes', 30)));
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('depart_at', today());
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('depart_at', '>', now())
            ->whereIn('status', [TripStatus::Scheduled->value, TripStatus::Boarding->value]);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', TripStatus::InProgress);
    }

    public function scopeForDriver(Builder $query, string $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', '.').'đ';
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function canBeBooked(): bool
    {
        return $this->status === TripStatus::Scheduled
            && $this->available_seats > 0
            && $this->depart_at->gt(now()->addMinutes((int) config('booking.min_lead_minutes', 30)));
    }

    public function isActive(): bool
    {
        return in_array($this->status, [TripStatus::Boarding, TripStatus::InProgress]);
    }
}
