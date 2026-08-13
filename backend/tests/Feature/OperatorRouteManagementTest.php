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

/** Bảng giá mặc định của nhà xe — không có nó thì mọi tuyến đều bị từ chối */
function giveFareRate(Operator $operator, array $attrs = []): void
{
    $operator->fareRates()->create(array_merge([
        'province_code' => null,
        'district_code' => null,
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

it('tạo route từ mã tỉnh huyện, không cần điểm dừng, giá tính theo km', function () {
    $operator = makeRouteOperator('A');
    giveFareRate($operator); // 20.000 + 1.000đ/km
    actingAsRouteOperator($operator);

    $response = $this->postJson('/api/operator/routes', routePayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.operator_id', $operator->id)
        ->assertJsonPath('data.origin_city', 'Hà Nội')
        ->assertJsonPath('data.origin_district', 'Quận Ba Đình')
        ->assertJsonPath('data.dest_city', 'Hải Phòng')
        ->assertJsonPath('data.dest_district', 'Quận Hồng Bàng')
        ->assertJsonPath('data.base_price', 125000) // 20.000 + 105 × 1.000
        ->assertJsonPath('data.pickup_service_area.code', 'HN')
        ->assertJsonPath('data.dropoff_service_area.code', 'HP');

    $routeId = $response->json('data.id');
    expect(Route::findOrFail($routeId)->operator_id)->toBe($operator->id);
    $this->assertDatabaseCount('route_stops', 0);
});

it('ưu tiên bảng giá của huyện điểm đi trước bảng giá tỉnh và mặc định', function () {
    $operator = makeRouteOperator('A');
    giveFareRate($operator, ['base_fare' => 0, 'price_per_km' => 500]);
    giveFareRate($operator, ['province_code' => HN_PROVINCE, 'base_fare' => 0, 'price_per_km' => 800]);
    giveFareRate($operator, [
        'province_code' => HN_PROVINCE, 'district_code' => HN_DISTRICT,
        'base_fare' => 10000, 'price_per_km' => 2000,
    ]);
    actingAsRouteOperator($operator);

    $this->postJson('/api/operator/routes', routePayload(['distance_km' => 100]))
        ->assertCreated()
        ->assertJsonPath('data.base_price', 210000); // 10.000 + 100 × 2.000
});

it('từ chối tạo tuyến khi nhà xe chưa cấu hình bảng giá', function () {
    $operator = makeRouteOperator('A');
    actingAsRouteOperator($operator);

    $this->postJson('/api/operator/routes', routePayload())
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('từ chối mã tỉnh huyện không hợp lệ và điểm đến trùng điểm đi', function () {
    $operator = makeRouteOperator('A');
    giveFareRate($operator);
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

it('cập nhật route đổi chiều thì đồng bộ lại service area và tính lại giá', function () {
    $operator = makeRouteOperator('A');
    giveFareRate($operator, ['base_fare' => 0, 'price_per_km' => 1000]);
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
    actingAsRouteOperator($operator);

    $this->putJson("/api/operator/routes/{$route->id}", [
        'name' => 'Hải Phòng → Hà Nội',
        'origin_province_code' => HP_PROVINCE,
        'origin_district_code' => HP_DISTRICT,
        'dest_province_code' => HN_PROVINCE,
        'dest_district_code' => HN_DISTRICT,
        'est_duration_min' => 160,
    ])->assertOk()
        ->assertJsonPath('data.pickup_service_area.code', 'HP')
        ->assertJsonPath('data.dropoff_service_area.code', 'HN')
        ->assertJsonPath('data.base_price', 105000);

    expect($route->refresh()->est_duration_min)->toBe(160);
    expect($route->origin_district)->toBe('Quận Hồng Bàng');
});

it('không cho sửa mỗi tỉnh mà giữ huyện cũ', function () {
    $operator = makeRouteOperator('A');
    giveFareRate($operator);
    $route = Route::create([
        'operator_id' => $operator->id, 'name' => 'Tuyến A', 'base_price' => 100000,
    ]);
    actingAsRouteOperator($operator);

    $this->putJson("/api/operator/routes/{$route->id}", ['origin_province_code' => HP_PROVINCE])
        ->assertStatus(422)
        ->assertJsonValidationErrors('origin_district_code');
});
