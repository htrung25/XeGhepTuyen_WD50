<?php

namespace App\Models;

use App\Observers\RouteObserver;
use App\Services\CityCodeResolver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(RouteObserver::class)]
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
        'pickup_service_area_id',
        'dropoff_service_area_id',
        'pickup_service_area_source',
        'dropoff_service_area_source',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_round_trip' => 'boolean',
            'base_price' => 'integer',
            'distance_km' => 'integer',
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

    public function pickupServiceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class, 'pickup_service_area_id');
    }

    public function dropoffServiceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class, 'dropoff_service_area_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForOperator(Builder $query, string $operatorId): Builder
    {
        return $query->where('operator_id', $operatorId);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function canBeDeleted(): bool
    {
        return ! $this->trips()->whereIn('status', ['scheduled', 'boarding'])->exists();
    }

    /**
     * Đồng bộ vùng phục vụ theo origin/dest_city — CHỈ MUTATE, không save.
     * Cột source='auto' → luôn tính lại (về null nếu thành phố không resolve
     * được; fail-closed sẽ chặn booking thay vì để vùng sai). source='manual'
     * → giữ nguyên. Trả về true nếu có thay đổi.
     */
    public function syncServiceAreasFromCities(): bool
    {
        $dirty = false;

        if ($this->pickup_service_area_source !== 'manual') {
            $code = CityCodeResolver::resolve((string) $this->origin_city);
            $areaId = $code ? ServiceArea::findByCityCode($code)?->id : null;

            if ($this->pickup_service_area_id !== $areaId) {
                $this->pickup_service_area_id = $areaId;
                $dirty = true;
            }
        }

        if ($this->dropoff_service_area_source !== 'manual') {
            $code = CityCodeResolver::resolve((string) $this->dest_city);
            $areaId = $code ? ServiceArea::findByCityCode($code)?->id : null;

            if ($this->dropoff_service_area_id !== $areaId) {
                $this->dropoff_service_area_id = $areaId;
                $dirty = true;
            }
        }

        return $dirty;
    }
}
