<?php

namespace App\Models;

use App\Enums\GenderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPassenger extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'seat_map_id',
        'full_name',
        'phone',
        'gender',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'gender'     => GenderType::class,
            'is_primary' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function seatMap(): BelongsTo
    {
        return $this->belongsTo(SeatMap::class);
    }
}
