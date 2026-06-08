<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'stop_name',
        'address',
        'lat',
        'lng',
        'stop_order',
        'offset_minutes',
        'is_pickup',
        'is_dropoff',
    ];

    protected function casts(): array
    {
        return [
            'lat'            => 'decimal:8',
            'lng'            => 'decimal:8',
            'stop_order'     => 'integer',
            'offset_minutes' => 'integer',
            'is_pickup'      => 'boolean',
            'is_dropoff'     => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePickup($query)
    {
        return $query->where('is_pickup', true);
    }

    public function scopeDropoff($query)
    {
        return $query->where('is_dropoff', true);
    }
}
