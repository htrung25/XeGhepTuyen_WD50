<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasUuids;

    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'booking_id',
        'user_id',
        'driver_id',
        'operator_id',
        'driver_rating',
        'vehicle_rating',
        'service_rating',
        'comment',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'driver_rating'  => 'integer',
            'vehicle_rating' => 'integer',
            'service_rating' => 'integer',
            'is_published'   => 'boolean',
            'created_at'     => 'datetime',
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

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getAvgRatingAttribute(): float
    {
        return round(($this->driver_rating + $this->vehicle_rating + $this->service_rating) / 3, 2);
    }
}
