<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Models\ServiceArea;
use App\Services\CityCodeResolver;
use App\Services\GeometryFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import ranh giới hành chính THẬT (GADM GeoJSON) vào service_areas — công cụ
 * quản lý dữ liệu địa lý production, tách khỏi seeder demo.
 *
 * php artisan service-area:import database/data/geo/gadm41_VNM_1.json \
 *   --province="Hà Nội" --code=HN --simplify=0.003 \
 *   --boundary-version=gadm41-2026-07 --dry-run
 */
class ImportServiceAreaCommand extends Command
{
    protected $signature = 'service-area:import
        {file : Đường dẫn file GeoJSON (FeatureCollection)}
        {--province= : Tên tỉnh cần lấy (so khớp NAME_1/VARNAME_1, không phân biệt dấu)}
        {--code= : Mã vùng chuẩn (HN/HP…)}
        {--simplify=0 : Dung sai ST_Simplify theo độ (~0.003 ≈ 330m); 0 = giữ nguyên}
        {--boundary-version= : Nhãn phiên bản, mặc định gadm-YYYY-MM-DD}
        {--dry-run : Chạy đủ các bước trong transaction rồi rollback}
        {--backfill-routes : Sau import, đồng bộ vùng cho các tuyến thiếu}';

    protected $description = 'Import ranh giới tỉnh (GeoJSON GADM) vào service_areas với validate + checksum';

    public function handle(GeometryFactory $factory): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->error('Chỉ chạy trên MySQL (cần hàm spatial).');

            return self::FAILURE;
        }

        $code = strtoupper((string) $this->option('code'));
        $province = (string) $this->option('province');
        if ($code === '' || $province === '') {
            $this->error('Thiếu --code hoặc --province.');

            return self::FAILURE;
        }

        $geometry = $this->extractGeometry((string) $this->argument('file'), $province);
        if ($geometry === null) {
            $this->error("Không tìm thấy tỉnh '{$province}' trong file.");

            return self::FAILURE;
        }

        $wkt = $factory->wktFromGeoJsonGeometry($geometry);
        $checksum = hash('sha256', $wkt);
        $version = (string) ($this->option('boundary-version') ?: 'gadm-'.now()->format('Y-m-d'));

        $existing = ServiceArea::where('code', $code)->first();
        if ($existing?->checksum === $checksum && ! $this->option('dry-run')) {
            $this->info("[{$code}] checksum không đổi — bỏ qua.");

            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $id = $existing?->id ?? (string) Str::orderedUuid();
            $geom = $factory->fromWkt($wkt);

            if ($existing) {
                DB::update("update service_areas set boundary = {$geom->sql} where id = ?", [...$geom->bindings, $id]);
            } else {
                DB::insert(
                    "insert into service_areas (id, name, code, boundary, is_active, created_at, updated_at)
                     values (?, ?, ?, {$geom->sql}, 1, now(), now())",
                    [$id, $province, $code, ...$geom->bindings],
                );
            }

            $tolerance = (float) $this->option('simplify');
            if ($tolerance > 0) {
                DB::update('update service_areas set boundary = ST_Simplify(boundary, ?) where id = ?', [$tolerance, $id]);
            }

            // Validate TRONG transaction — sai là rollback, không để geometry hỏng lọt DB
            $check = DB::selectOne(
                'select ST_SRID(boundary) as srid, ST_IsValid(boundary) as valid, ST_GeometryType(boundary) as gtype,
                        ST_NumPoints(ST_ExteriorRing(ST_GeometryN(boundary, 1))) as pts
                 from service_areas where id = ?',
                [$id],
            );

            if ((int) $check->srid !== GeometryFactory::SRID) {
                throw new \RuntimeException("SRID sai: {$check->srid} (cần 4326)");
            }
            if (! (bool) $check->valid) {
                throw new \RuntimeException('Geometry không hợp lệ (ST_IsValid=0) — thử giảm --simplify hoặc dùng dữ liệu gốc');
            }
            if (! str_contains(strtoupper((string) $check->gtype), 'MULTIPOLYGON')) {
                throw new \RuntimeException("Kiểu geometry sai: {$check->gtype}");
            }

            DB::update(
                'update service_areas set name = ?, source = ?, source_version = ?, boundary_version = ?, imported_at = now(), checksum = ? where id = ?',
                [$province, 'GADM', '4.1', $version, $checksum, $id],
            );

            $this->table(
                ['code', 'version', 'srid', 'valid', 'type', 'điểm ring 1', 'checksum'],
                [[$code, $version, $check->srid, $check->valid, $check->gtype, $check->pts, substr($checksum, 0, 12).'…']],
            );

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('DRY-RUN: đã rollback, không ghi gì.');

                return self::SUCCESS;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import thất bại (đã rollback): '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("[{$code}] import thành công.");

        if ($this->option('backfill-routes')) {
            $count = 0;
            Route::query()
                ->where(fn ($q) => $q->whereNull('pickup_service_area_id')->orWhereNull('dropoff_service_area_id'))
                ->chunkById(200, function ($routes) use (&$count) {
                    foreach ($routes as $route) {
                        if ($route->syncServiceAreasFromCities()) {
                            $route->save();
                            $count++;
                        }
                    }
                });
            $this->info("Backfill: {$count} tuyến được cập nhật vùng.");
        }

        return self::SUCCESS;
    }

    /** Tìm feature theo NAME_1/VARNAME_1 (so sánh không dấu qua normalize) */
    private function extractGeometry(string $file, string $province): ?array
    {
        $json = json_decode((string) file_get_contents($file), true);
        $target = CityCodeResolver::resolve($province) ?? mb_strtolower(trim(Str::ascii($province)));

        foreach ($json['features'] ?? [] as $feature) {
            foreach (['NAME_1', 'VARNAME_1'] as $key) {
                $name = (string) ($feature['properties'][$key] ?? '');
                $resolved = CityCodeResolver::resolve($name) ?? mb_strtolower(trim(Str::ascii($name)));
                if ($name !== '' && $resolved === $target) {
                    return $feature['geometry'];
                }
            }
        }

        return null;
    }
}
