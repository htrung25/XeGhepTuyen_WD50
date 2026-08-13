<?php

use App\Enums\UserRoleEnum;
use App\Models\Operator;
use App\Models\Route;
use App\Models\ServiceArea;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// Mã hành chính lấy từ resources/data/vn-provinces.json
const HN_PROVINCE = '01';      // Hà Nội
const HN_DISTRICT = '001';     // Quận Ba Đình
const HP_PROVINCE = '31';      // Hải Phòng
const HP_DISTRICT = '303';     // Quận Hồng Bàng

function makeRouteOperator(string $suffix): Operator
{
    $user = User::factory()->create(['role' => UserRoleEnum::Operator]);

    return Operator::create([
        'user_id' => $user->id,
        'company_name' => "Nhà xe {$suffix}",
        'business_license' => "GP-{$suffix}",
        'status' => 'verified',
    ]);
}

function actingAsRouteOperator(Operator $operator): void
{
    Sanctum::actingAs($operator->user, ['*'], 'sanctum');
    Sanctum::actingAs($operator->user, ['*'], 'operator');
}

/** Gán đơn giá/km cho một tuyến cụ thể (bảng giá theo tuyến) */
function giveFareRate(Operator $operator, string $routeId, array $attrs = []): void
{
    $operator->fareRates()->create(array_merge([
        'route_id' => $routeId,
        'base_fare' => 20000,
        'price_per_km' => 1000,
    ], $attrs));
}

function routePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Hà Nội → Hải Phòng',
        'origin_province_code' => HN_PROVINCE,
        'origin_district_code' => HN_DISTRICT,
        'dest_province_code' => HP_PROVINCE,
        'dest_district_code' => HP_DISTRICT,
        'distance_km' => 105,
        'est_duration_min' => 150,
        'is_round_trip' => false,
    ], $overrides);
}

beforeEach(function () {
    ServiceArea::create([
        'code' => 'HN', 'name' => 'Hà Nội',
        'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true,
    ]);
    ServiceArea::create([
        'code' => 'HP', 'name' => 'Hải Phòng',
        'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))', 'is_active' => true,
    ]);
});

it('tạo route từ mã tỉnh huyện, không cần điểm dừng, chưa có giá', function () {
    $operator = makeRouteOperator('A');
    actingAsRouteOperator($operator);

    $response = $this->postJson('/api/operator/routes', routePayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.operator_id', $operator->id)
        ->assertJsonPath('data.origin_city', 'Hà Nội')
        ->assertJsonPath('data.origin_district', 'Quận Ba Đình')
        ->assertJsonPath('data.dest_city', 'Hải Phòng')
        ->assertJsonPath('data.dest_district', 'Quận Hồng Bàng')
        // Tuyến tạo trước, gán giá sau ⇒ luôn bắt đầu ở "chưa có giá"
        ->assertJsonPath('data.base_price', 0)
        ->assertJsonPath('data.pickup_service_area.code', 'HN')
        ->assertJsonPath('data.dropoff_service_area.code', 'HP');

    $routeId = $response->json('data.id');
    expect(Route::findOrFail($routeId)->operator_id)->toBe($operator->id);
    $this->assertDatabaseCount('route_stops', 0);
});

it('gán đơn giá cho tuyến thì tuyến lấy lại giá theo km', function () {
    $operator = makeRouteOperator('A');
    actingAsRouteOperator($operator);

    $routeId = $this->postJson('/api/operator/routes', routePayload(['distance_km' => 100]))
        ->assertCreated()
        ->json('data.id');

    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['route_id' => $routeId, 'base_fare' => 20000, 'price_per_km' => 1000],
    ]])->assertOk();

    expect((int) Route::findOrFail($routeId)->base_price)->toBe(120000);
});

it('bỏ tuyến khỏi bảng giá thì tuyến quay lại chưa có giá', function () {
    $operator = makeRouteOperator('A');
    actingAsRouteOperator($operator);

    $routeId = $this->postJson('/api/operator/routes', routePayload())->json('data.id');
    giveFareRate($operator, $routeId);
    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['route_id' => $routeId, 'base_fare' => 0, 'price_per_km' => 1000],
    ]])->assertOk();

    $this->putJson('/api/operator/fare-rates', ['rates' => []])->assertOk();

    expect((int) Route::findOrFail($routeId)->base_price)->toBe(0);
});

it('từ chối mã tỉnh huyện không hợp lệ và điểm đến trùng điểm đi', function () {
    $operator = makeRouteOperator('A');
    actingAsRouteOperator($operator);

    $this->postJson('/api/operator/routes', routePayload(['origin_province_code' => '99']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('origin_province_code');

    // Huyện có thật nhưng không thuộc tỉnh đã chọn
    $this->postJson('/api/operator/routes', routePayload(['origin_district_code' => HP_DISTRICT]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('origin_district_code');

    $this->postJson('/api/operator/routes', routePayload([
        'dest_province_code' => HN_PROVINCE,
        'dest_district_code' => HN_DISTRICT,
    ]))->assertStatus(422)->assertJsonValidationErrors('dest_district_code');
});

it('chỉ liệt kê route thuộc operator đang đăng nhập', function () {
    $operator = makeRouteOperator('A');
    $other = makeRouteOperator('B');
    $ownRoute = Route::create([
        'operator_id' => $operator->id, 'name' => 'Tuyến A', 'base_price' => 100000,
    ]);
    Route::create([
        'operator_id' => $other->id, 'name' => 'Tuyến B', 'base_price' => 100000,
    ]);
    actingAsRouteOperator($operator);

    $this->getJson('/api/operator/routes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownRoute->id);
});

it('ẩn route của operator khác trên show update và destroy', function () {
    $operator = makeRouteOperator('A');
    $other = makeRouteOperator('B');
    $foreignRoute = Route::create([
        'operator_id' => $other->id, 'name' => 'Tuyến B', 'base_price' => 100000,
    ]);
    actingAsRouteOperator($operator);

    $this->getJson("/api/operator/routes/{$foreignRoute->id}")->assertNotFound();
    $this->putJson("/api/operator/routes/{$foreignRoute->id}", ['name' => 'Đã sửa'])->assertNotFound();
    $this->deleteJson("/api/operator/routes/{$foreignRoute->id}")->assertNotFound();

    expect($foreignRoute->fresh()->name)->toBe('Tuyến B');
});

it('cập nhật route đổi chiều thì đồng bộ lại service area và tính lại giá theo km', function () {
    $operator = makeRouteOperator('A');
    $route = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hà Nội → Hải Phòng',
        'origin_city' => 'Hà Nội',
        'origin_district' => 'Quận Ba Đình',
        'dest_city' => 'Hải Phòng',
        'dest_district' => 'Quận Hồng Bàng',
        'distance_km' => 105,
        'base_price' => 120000,
    ]);
    giveFareRate($operator, $route->id, ['base_fare' => 0, 'price_per_km' => 1000]);
    actingAsRouteOperator($operator);

    $this->putJson("/api/operator/routes/{$route->id}", [
        'name' => 'Hải Phòng → Hà Nội',
        'origin_province_code' => HP_PROVINCE,
        'origin_district_code' => HP_DISTRICT,
        'dest_province_code' => HN_PROVINCE,
        'dest_district_code' => HN_DISTRICT,
        'distance_km' => 120,
        'est_duration_min' => 160,
    ])->assertOk()
        ->assertJsonPath('data.pickup_service_area.code', 'HP')
        ->assertJsonPath('data.dropoff_service_area.code', 'HN')
        ->assertJsonPath('data.base_price', 120000); // 120 km × 1.000đ

    expect($route->refresh()->est_duration_min)->toBe(160);
    expect($route->origin_district)->toBe('Quận Hồng Bàng');
});

it('không cho sửa mỗi tỉnh mà giữ huyện cũ', function () {
    $operator = makeRouteOperator('A');
    $route = Route::create([
        'operator_id' => $operator->id, 'name' => 'Tuyến A', 'base_price' => 100000,
    ]);
    actingAsRouteOperator($operator);

    $this->putJson("/api/operator/routes/{$route->id}", ['origin_province_code' => HP_PROVINCE])
        ->assertStatus(422)
        ->assertJsonValidationErrors('origin_district_code');
});
