<?php

use App\DTOs\GeoCoordinate;
use App\Enums\UserRoleEnum;
use App\Exceptions\ServiceAreaNotConfiguredException;
use App\Models\Operator;
use App\Models\Route;
use App\Models\ServiceArea;
use App\Models\User;
use App\Services\ServiceAreaService;
use Laravel\Sanctum\Sanctum;

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

// ─── Task 2: đồng bộ vùng auto/manual qua RouteObserver ──────────────────────

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

// ─── Task 3: fail-closed khi tuyến chưa cấu hình vùng ────────────────────────

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
