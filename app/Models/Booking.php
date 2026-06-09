<?php

namespace App\Models;

use App\Enums\BookingPaymentStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_code',
        'user_id',
        'trip_id',
        'pickup_stop_id',
        'dropoff_stop_id',
        'pickup_address',
        'dropoff_address',
        'pickup_lat',
        'pickup_lng',
        'passenger_count',
        'contact_name',
        'contact_phone',
        'note',
        'subtotal',
        'discount_amount',
        'final_amount',
        'payment_method',
        'payment_status',
        'booking_status',
        'qr_code',
        'qr_token',
        'voucher_id',
        'expires_at',
        'confirmed_at',
        'checked_in_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return [
            'payment_method'  => PaymentMethod::class,
            'payment_status'  => BookingPaymentStatus::class,
            'booking_status'  => BookingStatus::class,
            'expires_at'      => 'datetime',
            'confirmed_at'    => 'datetime',
            'checked_in_at'   => 'datetime',
            'completed_at'    => 'datetime',
            'cancelled_at'    => 'datetime',
            'final_amount'    => 'integer',
            'subtotal'        => 'integer',
            'discount_amount' => 'integer',
            'passenger_count' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function pickupStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'pickup_stop_id');
    }

    public function dropoffStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'dropoff_stop_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('booking_status', BookingStatus::Pending);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('booking_status', BookingStatus::Confirmed);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTrip(Builder $query, string $tripId): Builder
    {
        return $query->where('trip_id', $tripId);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', BookingPaymentStatus::Unpaid);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', now())
                     ->where('payment_status', BookingPaymentStatus::Unpaid)
                     ->where('booking_status', BookingStatus::Pending);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->final_amount, 0, ',', '.') . 'đ';
    }

    // ─── Business Methods ─────────────────────────────────────────────────────

    public function canCancel(): bool
    {
        return in_array($this->booking_status, [BookingStatus::Pending, BookingStatus::Confirmed])
            && !$this->trip->isActive();
    }

    public function refundPercent(): int
    {
        $hoursUntilDepart = now()->diffInHours($this->trip->depart_at, false);

        if ($hoursUntilDepart > 24) {
            return 100;
        }

        if ($hoursUntilDepart > 4) {
            return 50;
        }

        return 0;
    }

    public function refundAmount(): int
    {
        return (int) ($this->final_amount * $this->refundPercent() / 100);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast()
            && $this->payment_status === BookingPaymentStatus::Unpaid;
    }
}
