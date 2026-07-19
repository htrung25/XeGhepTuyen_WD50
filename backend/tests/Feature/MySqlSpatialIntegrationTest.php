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
| (phpunit.xml các <env> không force nên biến môi trường thật sẽ thắng sqlite)
|--------------------------------------------------------------------------
*/
