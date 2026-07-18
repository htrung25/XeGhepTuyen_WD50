# Kích hoạt Geofencing Vùng Phục Vụ — Implementation Plan (v2, sau review)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Kích hoạt geofencing production-grade: map thành phố → vùng bằng mã chuẩn (không match tên hiển thị), tự đồng bộ vùng khi tuyến đổi thành phố (phân biệt auto/manual), **fail-closed** khi tuyến chưa cấu hình vùng, ranh giới thật từ GADM qua command import riêng (seeder chỉ cho demo), có MySQL integration test.

**Architecture:** `CityCodeResolver` (alias chuẩn hóa, giải pháp chuyển tiếp cho tới khi routes có cột city_code) → `ServiceArea::findByCityCode` (exact match). `RouteObserver@saving` mutate vùng trước khi persist (phủ mọi đường ghi, không recursion). `ServiceAreaService` fail-closed → 422 `SERVICE_AREA_NOT_CONFIGURED`. Dữ liệu địa lý: seeder demo (insert-only, version `demo-v1`) tách khỏi `service-area:import` (GADM, validate, checksum, dry-run).

**Tech Stack:** Laravel 13, MySQL 8 Spatial (SRID 4326), Pest (SQLite in-memory cho nghiệp vụ + MySQL 8 Docker cho spatial integration).

## Global Constraints

- KHÔNG viết `ST_GeomFromText`/`axis-order=long-lat` ngoài `GeometryFactory`; SQL raw dùng `{$geom->sql}` + bindings từ factory.
- WKT thứ tự **(lng lat)**; exterior ring **CCW** (GeoJSON RFC 7946 đã đúng chiều này sẵn).
- Code chạm hàm spatial phải guard `DB::getDriverName() !== 'mysql'`; riêng kiểm tra fail-closed (null/inactive) chạy trên MỌI driver.
- `vendor/bin/pint <files>` + `php artisan test` pass 100% trước mỗi commit; commit message kết thúc bằng `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.
- ⚠️ `.env` local trỏ **DB Laravel Cloud dùng chung**: Task 1–6 tuyệt đối không ghi DB thật (chỉ SQLite test + MySQL Docker localhost). Task 7 (production) chạy riêng sau khi duyệt, **data trước → deploy code sau**.
- KHÔNG đăng ký `ServiceAreaSeeder` vào `DatabaseSeeder`.
- GADM license: free cho academic/non-commercial (DATN đạt điều kiện); file GeoJSON lớn KHÔNG commit (gitignore), chỉ commit README nguồn.

**Known pre-existing bug (ngoài scope):** `Operator/RouteController::store()` ghi field không tồn tại trong schema (`route_code`, `duration_hours`…, thiếu `name`/`base_price` NOT NULL) — endpoint hỏng từ trước, fix ở PR riêng. Plan này không sửa và không test qua `store()`.

## Bảng phủ test theo yêu cầu review

| # | Yêu cầu | Task | Ghi chú |
|---|---|---|---|
| 1 | Đổi origin HN→HP tự cập nhật pickup area | T2 | test đổi thành phố THẬT |
| 2 | Area manual không bị cập nhật | T2 | cột `*_source` |
| 3 | Route chưa cấu hình area bị từ chối booking | T3 | fail-closed, sqlite chạy được |
| 4 | Area inactive bị từ chối | T3 | coi như chưa cấu hình |
| 5 | Tọa độ trên biên | T6 | ST_Intersects ⇒ hợp lệ (quy tắc đã chốt) |
| 6 | Tọa độ đảo lat/lng bị phát hiện | đã có | `GeoCoordinateTest` + HTTP 422 test (giữ nguyên) |
| 7 | SRID ≠ 4326 bị từ chối | T5+T6 | import assert ST_SRID=4326 |
| 8 | Boundary không hợp lệ không được import | T5+T6 | ST_IsValid trong transaction, rollback |
| 9 | Seeder re-run không bật lại vùng đã tắt | T4 | insert-only |
| 10 | Seeder không ghi đè boundary mới hơn | T4 | insert-only (skip mọi row tồn tại) |
| 11 | HN→HP và HP→HN đúng chiều | T6 | 2 route 2 chiều |
| 12 | MySQL spatial true/true/false | T6 | Hồ Gươm/NH lớn HP/Đà Nẵng |

---

### Task 1: `CityCodeResolver` + `ServiceArea::findByCityCode()`

**Files:**
- Create: `backend/app/Services/CityCodeResolver.php`
- Modify: `backend/app/Models/ServiceArea.php` (thêm method sau `scopeContainingPoint`)
- Test: `backend/tests/Unit/CityCodeResolverTest.php` (tạo mới), `backend/tests/Feature/ServiceAreaConfigTest.php` (tạo mới)

**Interfaces:**
- Produces: `CityCodeResolver::resolve(string $city): ?string` (mã 'HN'/'HP' hoặc null) và `ServiceArea::findByCityCode(string $code): ?ServiceArea` (exact match trên cột `code` unique, chỉ vùng active). Task 2, 5 dùng cả hai.
- Ghi chú kiến trúc: resolver là **giải pháp chuyển tiếp** — nâng cấp dài hạn là thêm cột `origin_city_code`/`dest_city_code` vào `routes` (ngoài scope plan này, đã ghi nhận).

- [ ] **Step 1: Viết test fail**

Tạo `backend/tests/Unit/CityCodeResolverTest.php`:

```php
<?php

use App\Services\CityCodeResolver;

it('resolve mọi biến thể tên Hà Nội về HN', function (string $city) {
    expect(CityCodeResolver::resolve($city))->toBe('HN');
})->with(['Hà Nội', 'ha noi', 'Ha Noi', 'Hanoi', 'HANOI', 'TP. Hà Nội', 'TP Hà Nội', 'Thành phố Hà Nội', '  Hà Nội  ']);

it('resolve mọi biến thể tên Hải Phòng về HP', function (string $city) {
    expect(CityCodeResolver::resolve($city))->toBe('HP');
})->with(['Hải Phòng', 'hai phong', 'Haiphong', 'TP. Hải Phòng', 'Thành phố Hải Phòng']);

it('trả null cho thành phố không hỗ trợ hoặc chuỗi rỗng', function (string $city) {
    expect(CityCodeResolver::resolve($city))->toBeNull();
})->with(['Đà Nẵng', 'Huế', '', '   ', 'H']);
```

Tạo `backend/tests/Feature/ServiceAreaConfigTest.php`:

```php
<?php

use App\Models\ServiceArea;

// SQLite không ép kiểu: boundary lưu WKT dạng text là đủ cho test không-spatial
function makeArea(string $code, string $name, bool $active = true): ServiceArea
{
    return ServiceArea::create([
        'code' => $code,
        'name' => $name,
        'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))',
        'is_active' => $active,
    ]);
}

it('findByCityCode match chính xác theo mã, không phân biệt hoa thường/khoảng trắng', function () {
    makeArea('HN', 'Hà Nội');

    expect(ServiceArea::findByCityCode('HN')?->code)->toBe('HN')
        ->and(ServiceArea::findByCityCode(' hn ')?->code)->toBe('HN')
        ->and(ServiceArea::findByCityCode('HP'))->toBeNull();
});

it('findByCityCode bỏ qua vùng đã tắt', function () {
    makeArea('HN', 'Hà Nội', active: false);

    expect(ServiceArea::findByCityCode('HN'))->toBeNull();
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter="CityCodeResolverTest|ServiceAreaConfigTest"`
Expected: FAIL — `Class "App\Services\CityCodeResolver" not found` / `undefined method findByCityCode`

- [ ] **Step 3: Implement**

Tạo `backend/app/Services/CityCodeResolver.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Map tên thành phố hiển thị → mã vùng chuẩn (HN/HP).
 *
 * GIẢI PHÁP CHUYỂN TIẾP: routes hiện lưu origin_city/dest_city dạng text tự do.
 * Không map nghiệp vụ bằng tên hiển thị — chuẩn hóa (bỏ dấu, thường hóa, bỏ tiền
 * tố TP/Thành phố) rồi tra bảng alias tĩnh. Nâng cấp dài hạn: thêm cột
 * origin_city_code/dest_city_code vào routes và bỏ resolver này.
 */
final class CityCodeResolver
{
    /** Alias đã chuẩn hóa (không dấu, chữ thường, không tiền tố) → mã vùng */
    private const ALIASES = [
        'ha noi' => 'HN',
        'hanoi' => 'HN',
        'hai phong' => 'HP',
        'haiphong' => 'HP',
    ];

    public static function resolve(string $city): ?string
    {
        $normalized = self::normalize($city);

        if ($normalized === '') {
            return null;
        }

        return self::ALIASES[$normalized] ?? null;
    }

    private static function normalize(string $city): string
    {
        $n = mb_strtolower(trim(Str::ascii($city)));           // 'TP. Hà Nội' → 'tp. ha noi'
        $n = preg_replace('/^(tp\.?|thanh pho)\s*/', '', $n);  // bỏ tiền tố TP/Thành phố

        return trim(preg_replace('/\s+/', ' ', $n));
    }
}
```

Thêm vào `backend/app/Models/ServiceArea.php` (sau `scopeContainingPoint`, trước Business Methods):

```php
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
```

- [ ] **Step 4: Chạy test, xác nhận pass**

Run: `cd backend && php artisan test --filter="CityCodeResolverTest|ServiceAreaConfigTest"`
Expected: PASS (5 tests)

- [ ] **Step 5: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint app/Services/CityCodeResolver.php app/Models/ServiceArea.php tests/Unit/CityCodeResolverTest.php tests/Feature/ServiceAreaConfigTest.php
php artisan test
git add -A && git commit -m "feat: CityCodeResolver + ServiceArea::findByCityCode (map theo ma chuan)

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 2: Đồng bộ vùng auto/manual + `RouteObserver` phủ mọi đường ghi

**Files:**
- Create: `backend/database/migrations/2026_07_19_000001_add_service_area_source_to_routes_table.php`
- Create: `backend/app/Observers/RouteObserver.php`
- Modify: `backend/app/Models/Route.php` (fillable + method + attribute `#[ObservedBy]`)
- Test: `backend/tests/Feature/ServiceAreaConfigTest.php` (thêm test)

**Interfaces:**
- Consumes: `CityCodeResolver::resolve`, `ServiceArea::findByCityCode` (Task 1).
- Produces: `Route::syncServiceAreasFromCities(): bool` — **chỉ mutate, KHÔNG save** (trả về có-thay-đổi); cột `pickup_service_area_source`/`dropoff_service_area_source` ('auto' mặc định | 'manual'); `RouteObserver@saving` gọi sync trước MỌI lần persist (create/update từ controller, admin, job, tinker — không recursion vì mutate trước khi ghi). Task 4, 5 gọi sync + save thủ công (seeder/command chạy ngoài event là an toàn tuyệt đối).
- Quy tắc: source `auto` → luôn tính lại theo thành phố (kể cả về null nếu không resolve được — kết hợp fail-closed Task 3 sẽ chặn booking thay vì giữ vùng sai); source `manual` → không đụng.

- [ ] **Step 1: Viết migration**

Tạo `backend/database/migrations/2026_07_19_000001_add_service_area_source_to_routes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            // 'auto': hệ thống tự gán lại theo origin/dest_city mỗi lần lưu.
            // 'manual': admin/operator gán tay — sync không được ghi đè.
            $table->string('pickup_service_area_source', 10)->default('auto')
                ->comment('auto|manual — nguồn gán pickup_service_area_id');
            $table->string('dropoff_service_area_source', 10)->default('auto')
                ->comment('auto|manual — nguồn gán dropoff_service_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['pickup_service_area_source', 'dropoff_service_area_source']);
        });
    }
};
```

- [ ] **Step 2: Viết test fail**

Thêm vào cuối `backend/tests/Feature/ServiceAreaConfigTest.php`:

```php
use App\Enums\UserRoleEnum;
use App\Models\Operator;
use App\Models\Route;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function makeRouteForGeo(string $origin = 'Hà Nội', string $dest = 'Hải Phòng'): Route
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX Geo Cfg',
        'business_license' => 'GP-'.fake()->unique()->numerify('####'), 'status' => 'verified',
    ]);

    return Route::create([
        'operator_id' => $operator->id, 'name' => "$origin - $dest",
        'origin_city' => $origin, 'dest_city' => $dest, 'base_price' => 150000,
    ]);
}

it('tạo route thì observer tự gán vùng theo thành phố', function () {
    makeArea('HN', 'Hà Nội');
    makeArea('HP', 'Hải Phòng');

    $route = makeRouteForGeo();

    expect($route->refresh()->pickupServiceArea?->code)->toBe('HN')
        ->and($route->dropoffServiceArea?->code)->toBe('HP');
});

it('đổi origin Hà Nội → Hải Phòng thì pickup area được gán LẠI thành HP', function () {
    makeArea('HN', 'Hà Nội');
    makeArea('HP', 'Hải Phòng');
    $route = makeRouteForGeo(); // pickup=HN sau khi tạo

    $route->update(['origin_city' => 'Hải Phòng']); // đổi thành phố THẬT

    expect($route->refresh()->pickupServiceArea?->code)->toBe('HP');
});

it('vùng gán manual không bị sync ghi đè khi đổi thành phố', function () {
    makeArea('HN', 'Hà Nội');
    $hp = makeArea('HP', 'Hải Phòng');
    $route = makeRouteForGeo();
    // admin gán tay: đánh dấu manual
    $route->update(['pickup_service_area_id' => $hp->id, 'pickup_service_area_source' => 'manual']);

    $route->update(['origin_city' => 'Đà Nẵng']);

    expect($route->refresh()->pickup_service_area_id)->toBe($hp->id)
        ->and($route->pickup_service_area_source)->toBe('manual');
});

it('đổi sang thành phố không resolve được thì vùng auto về null (fail-closed sẽ chặn booking)', function () {
    makeArea('HN', 'Hà Nội');
    makeArea('HP', 'Hải Phòng');
    $route = makeRouteForGeo();

    $route->update(['origin_city' => 'Đà Nẵng']);

    expect($route->refresh()->pickup_service_area_id)->toBeNull();
});

it('operator sửa tuyến qua API thì vùng đồng bộ theo thành phố mới', function () {
    makeArea('HN', 'Hà Nội');
    makeArea('HP', 'Hải Phòng');
    $route = makeRouteForGeo();
    $opUser = $route->operator->user;

    Sanctum::actingAs($opUser, ['*'], 'sanctum');
    Sanctum::actingAs($opUser, ['*'], 'operator');

    $this->putJson("/api/operator/routes/{$route->id}", ['origin_city' => 'Hải Phòng'])
        ->assertStatus(200);

    expect($route->refresh()->pickupServiceArea?->code)->toBe('HP');
});
```

- [ ] **Step 3: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=ServiceAreaConfigTest`
Expected: FAIL — vùng không được gán (chưa có observer/method)

- [ ] **Step 4: Implement**

Thêm vào `backend/app/Models/Route.php` — fillable thêm 2 cột source:

```php
        'pickup_service_area_id',
        'dropoff_service_area_id',
        'pickup_service_area_source',
        'dropoff_service_area_source',
```

Thêm attribute observer trên class (kèm `use App\Observers\RouteObserver;` và `use Illuminate\Database\Eloquent\Attributes\ObservedBy;`):

```php
#[ObservedBy(RouteObserver::class)]
class Route extends Model
```

Thêm method vào phần Helpers (sau `canBeDeleted()`):

```php
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
```

(import: `use App\Services\CityCodeResolver;`)

Tạo `backend/app/Observers/RouteObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Route;

/**
 * Đồng bộ vùng phục vụ trước MỌI lần persist Route — phủ controller, admin,
 * job, tinker mà không cần nhớ gọi. Hook `saving` chỉ mutate attribute trước
 * khi ghi nên không có recursion. Seeder/command dùng WithoutModelEvents hoặc
 * cần chắc chắn thì gọi thẳng syncServiceAreasFromCities().
 */
class RouteObserver
{
    public function saving(Route $route): void
    {
        $route->syncServiceAreasFromCities();
    }
}
```

- [ ] **Step 5: Chạy test, xác nhận pass**

Run: `cd backend && php artisan test --filter=ServiceAreaConfigTest`
Expected: PASS (7 tests — 2 cũ Task 1 + 5 mới)

- [ ] **Step 6: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint app/Models/Route.php app/Observers/RouteObserver.php app/Services/CityCodeResolver.php database/migrations/2026_07_19_000001_add_service_area_source_to_routes_table.php tests/Feature/ServiceAreaConfigTest.php
php artisan test
git add -A && git commit -m "feat: dong bo vung phuc vu auto/manual qua RouteObserver

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: Fail-closed — tuyến chưa cấu hình vùng thì CHẶN booking

**Files:**
- Create: `backend/app/Exceptions/ServiceAreaNotConfiguredException.php`
- Modify: `backend/app/Services/ServiceAreaService.php:38-46` (`validateBookingLocations`)
- Modify: `backend/app/Http/Controllers/Customer/BookingController.php` (thêm catch trong `store()`, cạnh catch `LocationOutsideServiceAreaException`)
- Modify: `backend/tests/Feature/ServiceAreaValidationTest.php` (test cũ phải gán vùng cho route)
- Modify: `backend/tests/Feature/CustomLocationBookingTest.php` (setup thêm vùng — nếu không sẽ đỏ vì fail-closed)
- Test: `backend/tests/Feature/ServiceAreaConfigTest.php` (thêm test)

**Interfaces:**
- Consumes: quan hệ `Route::pickupServiceArea/dropoffServiceArea` (đã có), makeArea/makeRouteForGeo (Task 1–2).
- Produces: `ServiceAreaNotConfiguredException` → HTTP 422 `{"code": "SERVICE_AREA_NOT_CONFIGURED", "message": "Tuyến chưa được cấu hình đầy đủ vùng phục vụ"}`. Kiểm tra null/inactive chạy trên MỌI driver (sqlite test được); chỉ phần ST_Intersects guard mysql.

- [ ] **Step 1: Viết test fail**

Thêm vào cuối `backend/tests/Feature/ServiceAreaConfigTest.php`:

```php
use App\Exceptions\ServiceAreaNotConfiguredException;
use App\Services\ServiceAreaService;
use App\DTOs\GeoCoordinate;

it('route chưa cấu hình vùng thì validateBookingLocations chặn (fail-closed)', function () {
    // KHÔNG tạo area nào → route auto-sync về null
    $route = makeRouteForGeo();

    app(ServiceAreaService::class)->validateBookingLocations(
        $route,
        GeoCoordinate::fromLatLng(21.0285, 105.8542),
        GeoCoordinate::fromLatLng(20.8609, 106.6822),
    );
})->throws(ServiceAreaNotConfiguredException::class);

it('vùng inactive coi như chưa cấu hình — chặn booking', function () {
    makeArea('HN', 'Hà Nội');
    makeArea('HP', 'Hải Phòng');
    $route = makeRouteForGeo();
    ServiceArea::where('code', 'HP')->update(['is_active' => false]);

    app(ServiceAreaService::class)->validateBookingLocations(
        $route->refresh(),
        GeoCoordinate::fromLatLng(21.0285, 105.8542),
        GeoCoordinate::fromLatLng(20.8609, 106.6822),
    );
})->throws(ServiceAreaNotConfiguredException::class);

it('route đã cấu hình đủ vùng active thì pass (sqlite bỏ qua phần spatial)', function () {
    makeArea('HN', 'Hà Nội');
    makeArea('HP', 'Hải Phòng');
    $route = makeRouteForGeo();

    app(ServiceAreaService::class)->validateBookingLocations(
        $route,
        GeoCoordinate::fromLatLng(21.0285, 105.8542),
        GeoCoordinate::fromLatLng(20.8609, 106.6822),
    );

    expect(true)->toBeTrue();
});
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=ServiceAreaConfigTest`
Expected: FAIL — `Class "App\Exceptions\ServiceAreaNotConfiguredException" not found`

- [ ] **Step 3: Implement**

Tạo `backend/app/Exceptions/ServiceAreaNotConfiguredException.php`:

```php
<?php

namespace App\Exceptions;

use Exception;

class ServiceAreaNotConfiguredException extends Exception
{
    public function __construct(string $message = 'Tuyến chưa được cấu hình đầy đủ vùng phục vụ')
    {
        parent::__construct($message);
    }
}
```

Sửa `backend/app/Services/ServiceAreaService.php` — thay toàn bộ thân `validateBookingLocations`:

```php
    public function validateBookingLocations(Route $route, GeoCoordinate $pickup, GeoCoordinate $dropoff): void
    {
        $route->loadMissing(['pickupServiceArea', 'dropoffServiceArea']);

        // FAIL-CLOSED: tuyến thiếu vùng hoặc vùng đã tắt → chặn booking thay vì
        // bypass geofencing. Kiểm tra này chạy trên MỌI driver (kể cả sqlite test).
        if (! $route->pickupServiceArea?->is_active || ! $route->dropoffServiceArea?->is_active) {
            throw new ServiceAreaNotConfiguredException;
        }

        // SQLite (test in-memory) không có hàm spatial → phần polygon chỉ chạy MySQL.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! $this->isPointInsideArea($route->pickupServiceArea, $pickup)) {
            throw new LocationOutsideServiceAreaException(
                "Điểm đón nằm ngoài vùng phục vụ ({$route->pickupServiceArea->name}) của tuyến"
            );
        }

        if (! $this->isPointInsideArea($route->dropoffServiceArea, $dropoff)) {
            throw new LocationOutsideServiceAreaException(
                "Điểm trả nằm ngoài vùng phục vụ ({$route->dropoffServiceArea->name}) của tuyến"
            );
        }
    }
```

(import thêm `use App\Exceptions\ServiceAreaNotConfiguredException;`; cập nhật docblock class: bỏ ý "tuyến chưa cấu hình → bỏ qua", thay bằng "fail-closed")

Sửa `backend/app/Http/Controllers/Customer/BookingController.php` — thêm catch vào `store()`, NGAY TRƯỚC catch `LocationOutsideServiceAreaException`:

```php
        } catch (ServiceAreaNotConfiguredException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'code' => 'SERVICE_AREA_NOT_CONFIGURED'], 422);
```

(import `use App\Exceptions\ServiceAreaNotConfiguredException;`)

- [ ] **Step 4: Sửa test cũ bị fail-closed làm đỏ**

`backend/tests/Feature/ServiceAreaValidationTest.php` — trong `setupServiceAreaBookingContext()`, NGAY TRƯỚC `return [$trip, $seat, $customer];` thêm:

```php
    // Fail-closed: route phải có vùng active thì luồng booking mới pass
    ServiceArea::updateOrCreate(['code' => 'HN'], ['name' => 'Hà Nội', 'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true]);
    ServiceArea::updateOrCreate(['code' => 'HP'], ['name' => 'Hải Phòng', 'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true]);
    $trip->route->syncServiceAreasFromCities() && $trip->route->save();
```

(import `use App\Models\ServiceArea;`; test "bỏ qua kiểm tra polygon khi tuyến chưa cấu hình vùng" trong file này ĐÃ SAI SPEC mới → XÓA test đó, hành vi mới được phủ bởi test fail-closed ở ServiceAreaConfigTest)

`backend/tests/Feature/CustomLocationBookingTest.php` — trong `setupCustomBookingContext()`, trước `return`, thêm đoạn giống hệt trên (kèm import `use App\Models\ServiceArea;`).

Route trong 2 file này tạo TRƯỚC khi có area (observer sync ra null) → bắt buộc gọi lại sync + save như đoạn trên.

- [ ] **Step 5: Chạy full suite, xác nhận pass**

Run: `cd backend && php artisan test`
Expected: PASS toàn bộ. Nếu còn test đỏ vì `SERVICE_AREA_NOT_CONFIGURED`: áp đúng pattern Step 4 (tạo 2 area + sync route) vào setup của test đó.

- [ ] **Step 6: Pint + commit**

```bash
cd backend
vendor/bin/pint app/Exceptions/ServiceAreaNotConfiguredException.php app/Services/ServiceAreaService.php app/Http/Controllers/Customer/BookingController.php tests/Feature/ServiceAreaConfigTest.php tests/Feature/ServiceAreaValidationTest.php tests/Feature/CustomLocationBookingTest.php
php artisan test
git add -A && git commit -m "feat: fail-closed khi tuyen chua cau hinh vung phuc vu (422 SERVICE_AREA_NOT_CONFIGURED)

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: Metadata boundary + seeder demo (insert-only, KHÔNG đăng ký DatabaseSeeder)

**Files:**
- Create: `backend/database/migrations/2026_07_19_000002_add_boundary_metadata_to_service_areas_table.php`
- Create: `backend/database/seeders/ServiceAreaSeeder.php`
- Test: `backend/tests/Feature/ServiceAreaConfigTest.php` (thêm test)

**Interfaces:**
- Consumes: `GeometryFactory::fromWkt` (đã có), `Route::syncServiceAreasFromCities` (Task 2).
- Produces: cột metadata `source`, `source_version`, `boundary_version`, `imported_at`, `checksum` trên `service_areas`; seeder demo `boundary_version='demo-v1'` — **CHỈ insert khi mã chưa tồn tại** (không update, không bật lại inactive, không ghi đè boundary import sau này). Quản lý dữ liệu production là việc của `service-area:import` (Task 5).

- [ ] **Step 1: Viết migration**

Tạo `backend/database/migrations/2026_07_19_000002_add_boundary_metadata_to_service_areas_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            // Truy vết nguồn gốc ranh giới — bắt buộc để phân biệt demo-v1 với
            // dữ liệu GADM thật, tránh seeder/import ghi đè nhầm lẫn nhau.
            $table->string('source', 50)->nullable()->comment('demo|GADM|OSM…');
            $table->string('source_version', 50)->nullable()->comment('VD: 4.1');
            $table->string('boundary_version', 50)->nullable()->comment('VD: demo-v1, gadm41-2026-07');
            $table->timestamp('imported_at')->nullable();
            $table->char('checksum', 64)->nullable()->comment('sha256 của WKT nguồn');
        });
    }

    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_version', 'boundary_version', 'imported_at', 'checksum']);
        });
    }
};
```

- [ ] **Step 2: Viết test fail**

Thêm vào cuối `backend/tests/Feature/ServiceAreaConfigTest.php`:

```php
use Database\Seeders\ServiceAreaSeeder;

it('seeder tạo 2 vùng demo HN/HP và backfill tuyến; chạy lại không nhân bản', function () {
    $route = makeRouteForGeo();

    $this->seed(ServiceAreaSeeder::class);
    $this->seed(ServiceAreaSeeder::class); // idempotent

    expect(ServiceArea::count())->toBe(2)
        ->and(ServiceArea::pluck('code')->sort()->values()->all())->toBe(['HN', 'HP'])
        ->and(ServiceArea::where('code', 'HN')->value('boundary_version'))->toBe('demo-v1')
        ->and($route->refresh()->pickupServiceArea?->code)->toBe('HN')
        ->and($route->dropoffServiceArea?->code)->toBe('HP');
});

it('seeder KHÔNG bật lại vùng đã bị tắt', function () {
    makeArea('HN', 'Hà Nội', active: false);

    $this->seed(ServiceAreaSeeder::class);

    expect(ServiceArea::where('code', 'HN')->value('is_active'))->toBeFalsy();
});

it('seeder KHÔNG ghi đè boundary/metadata của vùng đã tồn tại (VD đã import GADM)', function () {
    makeArea('HN', 'Hà Nội')->update(['boundary_version' => 'gadm41-2026-07', 'boundary' => 'GADM-DATA']);

    $this->seed(ServiceAreaSeeder::class);

    expect(ServiceArea::where('code', 'HN')->value('boundary_version'))->toBe('gadm41-2026-07')
        ->and(ServiceArea::where('code', 'HN')->value('boundary'))->toBe('GADM-DATA');
});
```

- [ ] **Step 3: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=ServiceAreaConfigTest`
Expected: FAIL — `Class "Database\Seeders\ServiceAreaSeeder" does not exist`

- [ ] **Step 4: Tạo seeder**

Tạo `backend/database/seeders/ServiceAreaSeeder.php`:

```php
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
            'source_version' => null,
            'boundary_version' => 'demo-v1',
            'imported_at' => now(),
            'checksum' => hash('sha256', $wkt),
        ];

        if (DB::getDriverName() !== 'mysql') {
            // SQLite (test): lưu WKT dạng text, đủ cho FK/logic không-spatial
            ServiceArea::create(['code' => $code, 'name' => $name, 'boundary' => $wkt, 'is_active' => true, ...$meta]);

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
```

Lưu ý: cần thêm các cột metadata vào `$fillable` của `ServiceArea`:

```php
        'source',
        'source_version',
        'boundary_version',
        'imported_at',
        'checksum',
```

- [ ] **Step 5: Chạy test, xác nhận pass** (KHÔNG sửa DatabaseSeeder — cố ý)

Run: `cd backend && php artisan test --filter=ServiceAreaConfigTest`
Expected: PASS (13 tests toàn file)

- [ ] **Step 6: Pint + full suite + commit**

```bash
cd backend
vendor/bin/pint database/seeders/ServiceAreaSeeder.php database/migrations/2026_07_19_000002_add_boundary_metadata_to_service_areas_table.php app/Models/ServiceArea.php tests/Feature/ServiceAreaConfigTest.php
php artisan test
git add -A && git commit -m "feat: seeder vung demo insert-only + metadata nguon goc boundary

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: Command `service-area:import` — ranh giới GADM cho production

**Files:**
- Create: `backend/app/Console/Commands/ImportServiceAreaCommand.php`
- Modify: `backend/app/Services/GeometryFactory.php` (thêm `wktFromGeoJsonGeometry`)
- Create: `backend/database/data/geo/README.md` (nguồn dữ liệu — commit)
- Create: `backend/database/data/geo/.gitignore` (nội dung: `*.json`)
- Create: `backend/tests/fixtures/geo/sample_province.json` (fixture nhỏ cho test)
- Test: `backend/tests/Unit/GeometryFactoryTest.php` (thêm test), `backend/tests/Feature/ServiceAreaConfigTest.php` (thêm test)

**Interfaces:**
- Consumes: `GeometryFactory::fromWkt`, `CityCodeResolver` (normalize để so tên tỉnh GADM), metadata cột (Task 4).
- Produces: `GeometryFactory::wktFromGeoJsonGeometry(array $geometry): string` (Polygon/MultiPolygon GeoJSON → WKT MULTIPOLYGON, thứ tự lng-lat giữ nguyên — GeoJSON RFC 7946 exterior ring đã CCW); command `service-area:import {file} {--province=} {--code=} {--simplify=0} {--boundary-version=} {--dry-run} {--backfill-routes}`.
- An toàn: abort nếu driver ≠ mysql; toàn bộ ghi trong transaction, **validate ST_IsValid + ST_SRID=4326 + ST_GeometryType=MULTIPOLYGON trước commit, sai → rollback**; checksum trùng → báo "không đổi, bỏ qua"; `--dry-run` → làm hết rồi rollback, in báo cáo.

- [ ] **Step 1: Viết test fail cho converter**

Thêm vào cuối `backend/tests/Unit/GeometryFactoryTest.php`:

```php
it('wktFromGeoJsonGeometry convert Polygon → MULTIPOLYGON WKT giữ nguyên thứ tự lng-lat', function () {
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [[[105.3, 20.5], [106.0, 20.5], [106.0, 21.4], [105.3, 20.5]]],
    ];

    expect(new GeometryFactory()->wktFromGeoJsonGeometry($geometry))
        ->toBe('MULTIPOLYGON(((105.3 20.5, 106 20.5, 106 21.4, 105.3 20.5)))');
});

it('wktFromGeoJsonGeometry convert MultiPolygon nhiều ring/đảo', function () {
    $geometry = [
        'type' => 'MultiPolygon',
        'coordinates' => [
            [[[0, 0], [1, 0], [1, 1], [0, 0]]],
            [[[5, 5], [6, 5], [6, 6], [5, 5]]],
        ],
    ];

    expect(new GeometryFactory()->wktFromGeoJsonGeometry($geometry))
        ->toBe('MULTIPOLYGON(((0 0, 1 0, 1 1, 0 0)), ((5 5, 6 5, 6 6, 5 5)))');
});

it('wktFromGeoJsonGeometry từ chối type khác Polygon/MultiPolygon', function () {
    new GeometryFactory()->wktFromGeoJsonGeometry(['type' => 'Point', 'coordinates' => [1, 2]]);
})->throws(InvalidArgumentException::class);
```

- [ ] **Step 2: Chạy test, xác nhận fail**

Run: `cd backend && php artisan test --filter=GeometryFactoryTest`
Expected: FAIL — undefined method `wktFromGeoJsonGeometry`

- [ ] **Step 3: Implement converter**

Thêm vào `backend/app/Services/GeometryFactory.php`:

```php
    /**
     * GeoJSON geometry (Polygon/MultiPolygon, RFC 7946: [lng, lat], exterior
     * ring CCW — khớp yêu cầu MySQL geographic SRS) → WKT MULTIPOLYGON.
     */
    public function wktFromGeoJsonGeometry(array $geometry): string
    {
        $type = $geometry['type'] ?? '';

        $polygons = match ($type) {
            'Polygon' => [$geometry['coordinates']],
            'MultiPolygon' => $geometry['coordinates'],
            default => throw new \InvalidArgumentException("GeoJSON type không hỗ trợ: {$type} (cần Polygon/MultiPolygon)"),
        };

        $wktPolygons = array_map(function (array $rings): string {
            $wktRings = array_map(function (array $ring): string {
                $points = array_map(fn (array $c) => $c[0].' '.$c[1], $ring);

                return '('.implode(', ', $points).')';
            }, $rings);

            return '('.implode(', ', $wktRings).')';
        }, $polygons);

        return 'MULTIPOLYGON('.implode(', ', $wktPolygons).')';
    }
```

- [ ] **Step 4: Chạy test converter pass**

Run: `cd backend && php artisan test --filter=GeometryFactoryTest`
Expected: PASS

- [ ] **Step 5: Viết test fail cho command (phần chạy được trên sqlite)**

Tạo fixture `backend/tests/fixtures/geo/sample_province.json`:

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {"NAME_1": "Hà Nội", "VARNAME_1": "Ha Noi"},
      "geometry": {"type": "Polygon", "coordinates": [[[105.3, 20.5], [106.0, 20.5], [106.0, 21.4], [105.3, 20.5]]]}
    }
  ]
}
```

Thêm vào cuối `backend/tests/Feature/ServiceAreaConfigTest.php`:

```php
it('service-area:import từ chối chạy trên driver không phải mysql', function () {
    $this->artisan('service-area:import', [
        'file' => base_path('tests/fixtures/geo/sample_province.json'),
        '--province' => 'Hà Nội',
        '--code' => 'HN',
    ])->assertFailed();
});
```

- [ ] **Step 6: Implement command**

Tạo `backend/app/Console/Commands/ImportServiceAreaCommand.php`:

```php
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

    /** Tìm feature theo NAME_1/VARNAME_1 (so sánh không dấu qua CityCodeResolver-style normalize) */
    private function extractGeometry(string $file, string $province): ?array
    {
        $json = json_decode((string) file_get_contents($file), true);
        $target = CityCodeResolver::resolve($province) ?? mb_strtolower(trim(\Illuminate\Support\Str::ascii($province)));

        foreach ($json['features'] ?? [] as $feature) {
            foreach (['NAME_1', 'VARNAME_1'] as $key) {
                $name = (string) ($feature['properties'][$key] ?? '');
                $resolved = CityCodeResolver::resolve($name) ?? mb_strtolower(trim(\Illuminate\Support\Str::ascii($name)));
                if ($name !== '' && $resolved === $target) {
                    return $feature['geometry'];
                }
            }
        }

        return null;
    }
}
```

Tạo `backend/database/data/geo/README.md`:

```markdown
# Dữ liệu ranh giới hành chính

- Nguồn: GADM 4.1 (https://gadm.org — free cho academic/non-commercial)
- Tải: https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_VNM_1.json.zip
- Giải nén `gadm41_VNM_1.json` vào thư mục này (file lớn — KHÔNG commit, đã gitignore)
- Import: `php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --simplify=0.003 --dry-run`
```

Tạo `backend/database/data/geo/.gitignore` với nội dung `*.json`.

- [ ] **Step 7: Chạy test, xác nhận pass + full suite**

Run: `cd backend && php artisan test`
Expected: PASS toàn bộ (test command sqlite assert FAILURE đúng như thiết kế).

- [ ] **Step 8: Pint + commit**

```bash
cd backend
vendor/bin/pint app/Console/Commands/ImportServiceAreaCommand.php app/Services/GeometryFactory.php tests/Unit/GeometryFactoryTest.php tests/Feature/ServiceAreaConfigTest.php
php artisan test
git add -A && git commit -m "feat: command service-area:import ranh gioi GADM (validate, checksum, dry-run)

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 6: MySQL Spatial integration tests (Docker localhost, có khóa an toàn)

**Files:**
- Create: `backend/tests/Feature/MySqlSpatialIntegrationTest.php`

**Interfaces:**
- Consumes: toàn bộ Task 1–5.
- Produces: bộ test spatial thật, **tự skip trên sqlite**; **từ chối chạy nếu DB host không phải localhost** — RefreshDatabase = `migrate:fresh` sẽ XÓA SẠCH DB, tuyệt đối không được trỏ nhầm vào DB Cloud.

- [ ] **Step 1: Viết test**

Tạo `backend/tests/Feature/MySqlSpatialIntegrationTest.php`:

```php
<?php

use App\DTOs\GeoCoordinate;
use App\Exceptions\LocationOutsideServiceAreaException;
use App\Models\ServiceArea;
use App\Services\ServiceAreaService;
use Database\Seeders\ServiceAreaSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Cần MySQL Spatial — chạy bằng Docker, xem hướng dẫn cuối file');
    }
    // KHÓA AN TOÀN: RefreshDatabase xóa sạch DB — cấm tuyệt đối DB từ xa (Cloud)
    if (! in_array(DB::getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
        $this->fail('NGUY HIỂM: integration test chỉ được chạy trên MySQL localhost (RefreshDatabase sẽ wipe DB).');
    }
    $this->seed(ServiceAreaSeeder::class);
});

it('điểm trong/ngoài polygon: Hồ Gươm ∈ HN, Nhà hát lớn ∈ HP, Đà Nẵng ∉ cả hai', function () {
    $svc = app(ServiceAreaService::class);
    $hn = ServiceArea::where('code', 'HN')->firstOrFail();
    $hp = ServiceArea::where('code', 'HP')->firstOrFail();

    expect($svc->isPointInsideArea($hn, GeoCoordinate::fromLatLng(21.0285, 105.8542)))->toBeTrue()
        ->and($svc->isPointInsideArea($hp, GeoCoordinate::fromLatLng(20.8609, 106.6822)))->toBeTrue()
        ->and($svc->isPointInsideArea($hn, GeoCoordinate::fromLatLng(16.0479, 108.2209)))->toBeFalse()
        ->and($svc->isPointInsideArea($hp, GeoCoordinate::fromLatLng(16.0479, 108.2209)))->toBeFalse();
});

it('điểm nằm ĐÚNG TRÊN biên được chấp nhận (ST_Intersects — quy tắc đã chốt)', function () {
    $hn = ServiceArea::where('code', 'HN')->firstOrFail();

    // đỉnh polygon demo-v1 của HN: (lng 105.30, lat 20.95)
    expect(app(ServiceAreaService::class)->isPointInsideArea($hn, GeoCoordinate::fromLatLng(20.95, 105.30)))->toBeTrue();
});

it('boundary sau seed có SRID 4326, hợp lệ, đúng kiểu MULTIPOLYGON, có SPATIAL INDEX', function () {
    $rows = DB::select(
        "select code, ST_SRID(boundary) srid, ST_IsValid(boundary) valid, ST_GeometryType(boundary) gtype
         from service_areas where code in ('HN','HP')"
    );

    expect($rows)->toHaveCount(2);
    foreach ($rows as $row) {
        expect((int) $row->srid)->toBe(4326)
            ->and((bool) $row->valid)->toBeTrue()
            ->and(strtoupper($row->gtype))->toContain('MULTIPOLYGON');
    }

    $index = DB::selectOne(
        "select count(*) c from information_schema.statistics
         where table_schema = database() and table_name = 'service_areas' and index_type = 'SPATIAL'"
    );
    expect((int) $index->c)->toBeGreaterThan(0);
});

it('validateBookingLocations đúng cả 2 chiều HN→HP và HP→HN', function () {
    $svc = app(ServiceAreaService::class);
    $hoGuom = GeoCoordinate::fromLatLng(21.0285, 105.8542);
    $nhaHatHP = GeoCoordinate::fromLatLng(20.8609, 106.6822);

    $routeHnHp = makeRouteForGeo('Hà Nội', 'Hải Phòng');
    $routeHpHn = makeRouteForGeo('Hải Phòng', 'Hà Nội');

    $svc->validateBookingLocations($routeHnHp, $hoGuom, $nhaHatHP);   // xuôi: pass
    $svc->validateBookingLocations($routeHpHn, $nhaHatHP, $hoGuom);   // ngược: pass

    // đảo chiều điểm trên tuyến HN→HP → điểm đón (Nhà hát HP) ngoài vùng HN
    expect(fn () => $svc->validateBookingLocations($routeHnHp, $nhaHatHP, $hoGuom))
        ->toThrow(LocationOutsideServiceAreaException::class);
});

/*
|--------------------------------------------------------------------------
| Cách chạy (MySQL 8 Docker — KHÔNG dùng DB Cloud):
|   docker run --rm -d --name xeghep-mysql-test -p 3307:3306 \
|     -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=xeghep_test mysql:8.0
|   cd backend && DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 \
|     DB_DATABASE=xeghep_test DB_USERNAME=root DB_PASSWORD=secret \
|     php artisan test --filter=MySqlSpatialIntegrationTest
| (phpunit.xml dùng <env force=false> nên biến môi trường thật sẽ thắng sqlite)
|--------------------------------------------------------------------------
*/
```

Lưu ý: `makeRouteForGeo` định nghĩa ở `ServiceAreaConfigTest.php` — Pest load chung một process; nếu chạy `--filter=MySqlSpatialIntegrationTest` riêng lẻ báo undefined function thì chuyển 2 helper `makeArea`/`makeRouteForGeo` vào `tests/Pest.php` (bước thực thi xử lý).

- [ ] **Step 2: Xác nhận suite sqlite skip sạch**

Run: `cd backend && php artisan test --filter=MySqlSpatialIntegrationTest`
Expected: 4 tests SKIPPED (driver sqlite).

- [ ] **Step 3: Chạy thật với Docker MySQL (nếu Docker khả dụng trên máy)**

Chạy đúng 2 lệnh trong comment cuối file test.
Expected: PASS 4 tests. Nếu Docker không khả dụng: đánh dấu bước này để chạy ở CI/máy khác trước Task 7 — **Task 7 không được chạy khi bước này chưa PASS**.

- [ ] **Step 4: Pint + commit**

```bash
cd backend
vendor/bin/pint tests/Feature/MySqlSpatialIntegrationTest.php
php artisan test
git add -A && git commit -m "test: MySQL spatial integration (diem trong/ngoai, bien, SRID, 2 chieu tuyen)

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 7: Production rollout — DATA TRƯỚC, CODE SAU (chỉ chạy sau khi duyệt + Task 6 PASS)

> Nguyên tắc trình tự: DB Cloud dùng chung với local nên **import dữ liệu + backfill chạy từ máy local TRƯỚC** (code production cũ chưa fail-closed, không ảnh hưởng booking đang chạy), **sau đó mới deploy code mới** (fail-closed bật khi dữ liệu đã đầy đủ). Không có khoảng trống chặn nhầm booking.

**Files:** không sửa code.

- [ ] **Step 1: Backup 2 bảng bị ảnh hưởng**

Tạo backup/snapshot từ dashboard Laravel Cloud, HOẶC dump cục bộ (điền credentials từ `backend/.env`):

```bash
mysqldump -h <DB_HOST> -P 3306 -u <DB_USERNAME> -p<DB_PASSWORD> main service_areas routes > ~/backup_geo_$(date +%Y%m%d_%H%M).sql
```
Expected: file backup > 0 byte. KHÔNG tiếp tục nếu backup thất bại.

- [ ] **Step 2: Xác minh schema trước import**

```bash
cd backend && php artisan tinker --execute="
print_r(\Illuminate\Support\Facades\DB::selectOne('show create table service_areas')->{'Create Table'});
"
```
Expected trong output: `` `boundary` multipolygon NOT NULL /*!80003 SRID 4326 */ `` và `SPATIAL KEY` trên `boundary`. Nếu thiếu SPATIAL KEY → dừng, kiểm tra migration `2026_07_18_000001`.

- [ ] **Step 3: Tải GADM + dry-run import**

```bash
cd backend/database/data/geo
curl -LO https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_VNM_1.json.zip && unzip -o gadm41_VNM_1.json.zip
cd ../../..
php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --simplify=0.003 --boundary-version=gadm41-2026-07 --dry-run
php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hải Phòng" --code=HP --simplify=0.003 --boundary-version=gadm41-2026-07 --dry-run
```
Expected mỗi lệnh: bảng kết quả `srid=4326, valid=1, type=MULTIPOLYGON` + dòng `DRY-RUN: đã rollback`. Sai bất kỳ giá trị nào → dừng, không import thật.

- [ ] **Step 4: Import thật + backfill tuyến**

```bash
php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hà Nội" --code=HN --simplify=0.003 --boundary-version=gadm41-2026-07
php artisan service-area:import database/data/geo/gadm41_VNM_1.json --province="Hải Phòng" --code=HP --simplify=0.003 --boundary-version=gadm41-2026-07 --backfill-routes
```
Expected: `[HN]/[HP] import thành công` + `Backfill: 2 tuyến được cập nhật vùng`.

- [ ] **Step 5: Xác minh dữ liệu + spatial trên production**

```bash
php artisan tinker --execute="
print_r(\Illuminate\Support\Facades\DB::select(\"select code, boundary_version, ST_SRID(boundary) srid, ST_IsValid(boundary) valid, ST_GeometryType(boundary) gtype from service_areas\"));
echo 'routes đủ vùng: '.\App\Models\Route::whereNotNull('pickup_service_area_id')->whereNotNull('dropoff_service_area_id')->count().'/'.\App\Models\Route::count().PHP_EOL;
\$svc = app(\App\Services\ServiceAreaService::class);
\$hn = \App\Models\ServiceArea::where('code','HN')->firstOrFail();
var_dump(\$svc->isPointInsideArea(\$hn, \App\DTOs\GeoCoordinate::fromLatLng(21.0285, 105.8542)));  // Hồ Gươm: true
var_dump(\$svc->isPointInsideArea(\$hn, \App\DTOs\GeoCoordinate::fromLatLng(16.0479, 108.2209)));  // Đà Nẵng: false (true = ring ngược!)
"
```
Expected: 2 vùng `gadm41-2026-07 / 4326 / 1 / MULTIPOLYGON`; `routes đủ vùng: 2/2`; `true` rồi `false`.

- [ ] **Step 6: Deploy code (fail-closed đi sau dữ liệu)**

Merge + deploy Laravel Cloud như thường lệ (`php artisan migrate --force && php artisan optimize` — 2 migration mới của plan đã nằm trong repo).

- [ ] **Step 7: Smoke test API sau deploy**

- Booking điểm đón nội thành Hà Nội → **201**.
- Booking `pickup_lat=16.0479, pickup_lng=108.2209` (Đà Nẵng) → **422** `code: LOCATION_OUTSIDE_SERVICE_AREA`.
- Tạo tuyến mới origin "Đà Nẵng" (chưa có vùng) rồi thử booking → **422** `code: SERVICE_AREA_NOT_CONFIGURED`.
- Đặt vé bình thường của khách thật không bị ảnh hưởng (theo dõi log lỗi 30 phút đầu).

Rollback nếu sự cố: restore 2 bảng từ backup Step 1 + revert deploy trên Cloud.

---

## Self-Review (đã chạy lại theo review v1)

1. **Coverage 10 điểm review:** (1) resolver+mã chuẩn ✔T1 · (2) auto/manual + test đổi thành phố thật ✔T2 · (3) GADM+simplify+metadata ✔T5/T7, demo tách riêng ✔T4 · (4) seeder insert-only ✔T4 · (5) không đăng ký DatabaseSeeder ✔T4 · (6) MySQL integration test ✔T6 · (7) SHOW CREATE TABLE + SRID/IsValid ✔T6/T7 · (8) chunkById + resolve theo mã (không load all areas) ✔T2/T4/T5 · (9) observer thay controller-hook (xem pushback trong thảo luận) ✔T2 · (10) fail-closed + SERVICE_AREA_NOT_CONFIGURED ✔T3, trình tự data-trước-code-sau ✔T7.
2. **12 test bổ sung:** map đủ ở bảng đầu tài liệu.
3. **Placeholder scan:** không còn "nâng cấp sau" ở đường production; mọi code/lệnh/expected đầy đủ.
4. **Type consistency:** `resolve(): ?string` ↔ T2/T5 ✔ · `findByCityCode(): ?ServiceArea` ↔ T2 ✔ · `syncServiceAreasFromCities(): bool` (mutate-only) ↔ observer/seeder/command đều `sync() && save()` hoặc gọi trong `saving` ✔ · `wktFromGeoJsonGeometry(array): string` ↔ T5 command ✔.
