<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasUuids;

    protected $table = 'routes';

    protected $fillable = [
        'operator_id',
        'name',
        'origin_city',
        'dest_city',
        'distance_km',
        'est_duration_min',
        'base_price',
        'is_active',
        'is_round_trip',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'is_round_trip'  => 'boolean',
            'base_price'     => 'integer',
            'distance_km'    => 'integer',
            'est_duration_min' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('stop_order');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOperator($query, string $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function canBeDeleted(): bool
    {
        return !$this->trips()->whereIn('status', ['scheduled', 'boarding'])->exists();
    }
}
