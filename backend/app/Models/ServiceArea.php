<?php

namespace App\Models;

use App\DTOs\GeoCoordinate;
use App\Services\GeometryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServiceArea extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'boundary',
        'is_active',
        'source',
        'source_version',
        'boundary_version',
        'imported_at',
        'checksum',
    ];

    /** boundary là geometry nhị phân — không được lọt vào JSON response */
    protected $hidden = ['boundary'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Vùng chứa tọa độ — geofencing bằng MySQL Spatial (ST_Intersects, điểm trên
     * biên vẫn hợp lệ). Geometry dựng qua GeometryFactory; chỉ chạy trên MySQL.
     */
    public function scopeContainingPoint(Builder $query, GeoCoordinate $point): Builder
    {
        $geom = app(GeometryFactory::class)->point($point);

        return $query->whereRaw("ST_Intersects(boundary, {$geom->sql})", $geom->bindings);
    }

    // ─── Lookup ───────────────────────────────────────────────────────────────

    /**
     * Tra vùng theo MÃ chuẩn (HN/HP) — exact match trên cột unique, chỉ vùng active.
     * Không match theo tên hiển thị; tên thành phố phải qua CityCodeResolver trước.
     */
    public static function findByCityCode(string $code): ?self
    {
        return static::query()
            ->active()
            ->where('code', strtoupper(trim($code)))
            ->first();
    }

    // ─── Business Methods ─────────────────────────────────────────────────────

    /**
     * Ghi ranh giới từ WKT MULTIPOLYGON (tọa độ theo thứ tự lng lat — chuẩn GeoJSON).
     * Geometry dựng qua GeometryFactory — nơi duy nhất giữ quy ước SRID/axis-order.
     */
    public function setBoundaryFromWkt(string $wkt): void
    {
        $geom = app(GeometryFactory::class)->fromWkt($wkt);

        DB::update(
            "update service_areas set boundary = {$geom->sql} where id = ?",
            [...$geom->bindings, $this->getKey()],
        );
    }
}
