<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Models\ServiceArea;
use App\Services\CityCodeResolver;
use App\Services\GeometryFactory;
use App\Services\VietnamAdministrative;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import ranh giới TỈNH + TỪNG HUYỆN vào service_areas từ dữ liệu dvhcvn
 * (data/gis/{mã tỉnh}.json — mã hành chính GSO, trùng với
 * resources/data/vn-provinces.json mà tuyến đang dùng).
 *
 *   php artisan service-area:import-districts database/data/geo/dvhcvn_01.json \
 *     database/data/geo/dvhcvn_31.json --prune --backfill-routes --dry-run
 *
 * Quy ước mã vùng:
 *   - Tỉnh:  HN, HP           (giữ nguyên mã cũ ⇒ cập nhật tại chỗ, không phá FK)
 *   - Huyện: HN-001, HP-303   (mã tỉnh ngắn + mã huyện GSO)
 *
 * TÊN vùng luôn lấy từ vn-provinces.json chứ không lấy tên trong file GIS: tuyến
 * lưu origin_district theo đúng tên trong catalog, lệch tên là hỏng đối chiếu
 * (VD 311 catalog "Thành phố Thuỷ Nguyên" vs GIS "Huyện Thuỷ Nguyên").
 *
 * Import full-resolution — xem ghi chú ST_Simplify ở ImportServiceAreaCommand.
 */
class ImportDistrictServiceAreaCommand extends Command
{
    protected $signature = 'service-area:import-districts
        {files* : Một hoặc nhiều file GIS dvhcvn (data/gis/{mã tỉnh}.json)}
        {--boundary-version= : Nhãn phiên bản, mặc định dvhcvn-YYYY-MM-DD}
        {--prune : Xoá vùng huyện cũ của tỉnh đó không còn trong file}
        {--backfill-routes : Sau import, đồng bộ lại vùng cho các tuyến}
        {--dry-run : Chạy đủ các bước trong transaction rồi rollback}';

    protected $description = 'Import ranh giới tỉnh + từng huyện (dvhcvn GeoJSON) vào service_areas';

    private int $inserted = 0;

    private int $updated = 0;

    private int $unchanged = 0;

    /** @var array<int, array{code: string, reason: string}> */
    private array $failed = [];

    public function handle(GeometryFactory $factory): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->error('Chỉ chạy trên MySQL (cần hàm spatial).');

            return self::FAILURE;
        }

        $version = (string) ($this->option('boundary-version') ?: 'dvhcvn-'.now()->format('Y-m-d'));

        DB::beginTransaction();

        try {
            foreach ((array) $this->argument('files') as $path) {
                $this->importFile($factory, (string) $path, $version);
            }

            if ($this->option('backfill-routes')) {
                $this->backfillRoutes();
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('DRY-RUN: đã rollback, DB không đổi.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import thất bại (đã rollback): '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['thêm mới', 'cập nhật', 'không đổi', 'lỗi'],
            [[$this->inserted, $this->updated, $this->unchanged, count($this->failed)]],
        );

        foreach ($this->failed as $fail) {
            $this->warn("[{$fail['code']}] {$fail['reason']}");
        }

        return $this->failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function importFile(GeometryFactory $factory, string $path, string $version): void
    {
        if (! is_file($path)) {
            throw new \RuntimeException("Không tìm thấy file: {$path}");
        }

        $data = json_decode((string) file_get_contents($path), true);
        $provinceCode = (string) ($data['level1_id'] ?? '');
        $province = VietnamAdministrative::findProvince($provinceCode);

        if (! $province) {
            throw new \RuntimeException("Mã tỉnh '{$provinceCode}' không có trong vn-provinces.json: {$path}");
        }

        // Mã ngắn phải là mã CityCodeResolver đang dùng (HN/HP) để không phá vỡ
        // dữ liệu và luồng geofencing hiện tại.
        $shortCode = CityCodeResolver::resolve($province['name']);

        if ($shortCode === null) {
            throw new \RuntimeException("Chưa có mã vùng ngắn cho tỉnh '{$province['name']}' (CityCodeResolver)");
        }

        $this->line("── {$province['name']} ({$shortCode}) — ".count($data['level2s'] ?? []).' huyện');

        // Ranh giới tỉnh lấy CÙNG NGUỒN với huyện: nếu tỉnh dùng GADM còn huyện
        // dùng dvhcvn thì có điểm nằm trong tỉnh mà không thuộc huyện nào.
        $this->upsertArea(
            $factory,
            $shortCode,
            $province['name'],
            ['type' => $data['type'] ?? 'MultiPolygon', 'coordinates' => $data['coordinates'] ?? []],
            $version,
        );

        $seen = [];

        foreach ($data['level2s'] ?? [] as $district) {
            $districtCode = (string) ($district['level2_id'] ?? '');
            $catalog = VietnamAdministrative::findDistrict($provinceCode, $districtCode);

            if (! $catalog) {
                $this->failed[] = [
                    'code' => "{$shortCode}-{$districtCode}",
                    'reason' => "Mã huyện {$districtCode} không có trong vn-provinces.json — bỏ qua",
                ];

                continue;
            }

            $code = "{$shortCode}-{$districtCode}";
            $seen[] = $code;

            $this->upsertArea(
                $factory,
                $code,
                $catalog['name'].', '.$province['name'],
                ['type' => $district['type'] ?? 'MultiPolygon', 'coordinates' => $district['coordinates'] ?? []],
                $version,
            );
        }

        if ($this->option('prune')) {
            $this->prune($shortCode, $seen);
        }
    }

    private function upsertArea(GeometryFactory $factory, string $code, string $name, array $geometry, string $version): void
    {
        $wkt = $factory->wktFromGeoJsonGeometry($geometry);
        $checksum = hash('sha256', $wkt);
        $existing = ServiceArea::where('code', $code)->first();

        if ($existing && $existing->checksum === $checksum) {
            $this->unchanged++;

            return;
        }

        $id = $existing?->id ?? (string) Str::orderedUuid();
        $geom = $factory->fromWkt($wkt);

        if ($existing) {
            DB::update("update service_areas set boundary = {$geom->sql} where id = ?", [...$geom->bindings, $id]);
            $this->updated++;
        } else {
            DB::insert(
                "insert into service_areas (id, name, code, boundary, is_active, created_at, updated_at)
                 values (?, ?, ?, {$geom->sql}, 1, now(), now())",
                [$id, $name, $code, ...$geom->bindings],
            );
            $this->inserted++;
        }

        // Validate NGAY sau khi ghi, trong cùng transaction — geometry hỏng thì
        // ném ra để rollback toàn bộ, không để lọt vào DB.
        $check = DB::selectOne(
            'select ST_SRID(boundary) as srid, ST_IsValid(boundary) as valid, ST_GeometryType(boundary) as gtype
             from service_areas where id = ?',
            [$id],
        );

        if ((int) $check->srid !== GeometryFactory::SRID) {
            throw new \RuntimeException("[{$code}] SRID sai: {$check->srid} (cần 4326)");
        }
        if (! (bool) $check->valid) {
            throw new \RuntimeException("[{$code}] Geometry không hợp lệ (ST_IsValid=0) — xử lý dữ liệu nguồn trước khi import");
        }
        if (! str_contains(strtoupper((string) $check->gtype), 'MULTIPOLYGON')) {
            throw new \RuntimeException("[{$code}] Kiểu geometry sai: {$check->gtype}");
        }

        DB::update(
            'update service_areas set name = ?, source = ?, source_version = ?, boundary_version = ?, imported_at = now(), checksum = ?, updated_at = now() where id = ?',
            [$name, 'dvhcvn', 'master', $version, $checksum, $id],
        );
    }

    /** Xoá vùng huyện của tỉnh này không còn trong file nguồn */
    private function prune(string $shortCode, array $keepCodes): void
    {
        $stale = ServiceArea::where('code', 'like', $shortCode.'-%')
            ->when($keepCodes !== [], fn ($q) => $q->whereNotIn('code', $keepCodes))
            ->pluck('code', 'id');

        if ($stale->isEmpty()) {
            return;
        }

        ServiceArea::whereIn('id', $stale->keys())->delete();
        $this->warn('Đã xoá vùng cũ: '.$stale->values()->implode(', '));
    }

    /** Gán lại vùng cho các tuyến (cột source='auto') sau khi ranh giới đổi */
    private function backfillRoutes(): void
    {
        $synced = 0;

        Route::query()->chunkById(200, function ($routes) use (&$synced) {
            foreach ($routes as $route) {
                if ($route->syncServiceAreasFromCities()) {
                    $route->save();
                    $synced++;
                }
            }
        });

        $this->info("Đã đồng bộ lại vùng cho {$synced} tuyến.");
    }
}
