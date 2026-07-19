<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\ServiceArea;
use App\Services\GeometryFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Vùng phục vụ DEMO (boundary_version=demo-v1) — polygon bao thô CHỈ cho
 * local/dev/demo kỹ thuật. KHÔNG đăng ký vào DatabaseSeeder. Production dùng
 * ranh giới GADM qua `php artisan service-area:import` (nguồn sự thật).
 *
 * INSERT-ONLY: mã đã tồn tại → bỏ qua tuyệt đối (không update name/boundary,
 * không bật lại is_active) — seeder không phải công cụ quản lý dữ liệu địa lý.
 */
class ServiceAreaSeeder extends Seeder
{
    /** WKT (lng lat), exterior ring CCW */
    private const AREAS = [
        [
            'code' => 'HN',
            'name' => 'Hà Nội',
            'wkt' => 'MULTIPOLYGON(((105.30 20.95, 105.45 20.60, 105.85 20.55, 106.02 20.85, 106.02 21.15, 105.88 21.35, 105.55 21.40, 105.32 21.20, 105.30 20.95)))',
        ],
        [
            'code' => 'HP',
            'name' => 'Hải Phòng',
            'wkt' => 'MULTIPOLYGON(((106.30 20.80, 106.45 20.62, 106.80 20.55, 107.15 20.68, 107.18 20.92, 106.85 21.05, 106.50 21.02, 106.30 20.80)))',
        ],
    ];

    public function run(): void
    {
        foreach (self::AREAS as $area) {
            $this->insertIfMissing($area['code'], $area['name'], $area['wkt']);
        }

        // Backfill tuyến thiếu vùng — chunk để không load toàn bộ vào memory
        Route::query()
            ->where(fn ($q) => $q->whereNull('pickup_service_area_id')->orWhereNull('dropoff_service_area_id'))
            ->chunkById(200, function ($routes) {
                foreach ($routes as $route) {
                    if ($route->syncServiceAreasFromCities()) {
                        $route->save();
                    }
                }
            });
    }

    private function insertIfMissing(string $code, string $name, string $wkt): void
    {
        if (ServiceArea::where('code', $code)->exists()) {
            return; // insert-only: không bao giờ update vùng đã có
        }

        $meta = [
            'source' => 'demo',
            'boundary_version' => 'demo-v1',
            'checksum' => hash('sha256', $wkt),
        ];

        if (DB::getDriverName() !== 'mysql') {
            // SQLite (test): lưu WKT dạng text, đủ cho FK/logic không-spatial
            ServiceArea::create([
                'code' => $code, 'name' => $name, 'boundary' => $wkt, 'is_active' => true,
                'imported_at' => now(), ...$meta,
            ]);

            return;
        }

        $geom = app(GeometryFactory::class)->fromWkt($wkt);
        DB::insert(
            "insert into service_areas (id, name, code, boundary, is_active, source, boundary_version, imported_at, checksum, created_at, updated_at)
             values (?, ?, ?, {$geom->sql}, 1, ?, ?, now(), ?, now(), now())",
            [(string) Str::orderedUuid(), $name, $code, ...$geom->bindings, $meta['source'], $meta['boundary_version'], $meta['checksum']],
        );
    }
}
