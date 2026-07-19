<?php

use App\Enums\UserRoleEnum;
use App\Models\Operator;
use App\Models\Route;
use App\Models\ServiceArea;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

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

function routePayload(): array
{
    return [
        'name' => 'Hà Nội → Hải Phòng',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'distance_km' => 105,
        'est_duration_min' => 150,
        'base_price' => 120000,
        'is_round_trip' => false,
        'stops' => [
            [
                'stop_name' => 'Hồ Gươm',
                'address' => 'Hoàn Kiếm, Hà Nội',
                'lat' => 21.0285,
                'lng' => 105.8542,
                'stop_order' => 1,
                'offset_minutes' => 0,
                'is_pickup' => true,
                'is_dropoff' => false,
            ],
            [
                'stop_name' => 'Nhà hát lớn Hải Phòng',
                'address' => 'Hồng Bàng, Hải Phòng',
                'lat' => 20.8609,
                'lng' => 106.6822,
                'stop_order' => 2,
                'offset_minutes' => 150,
                'is_pickup' => false,
                'is_dropoff' => true,
            ],
        ],
    ];
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

it('tạo route bằng đúng contract và tự gán service area', function () {
    $operator = makeRouteOperator('A');
    actingAsRouteOperator($operator);

    $response = $this->postJson('/api/operator/routes', routePayload())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.operator_id', $operator->id)
        ->assertJsonPath('data.name', 'Hà Nội → Hải Phòng')
        ->assertJsonPath('data.est_duration_min', 150)
        ->assertJsonPath('data.base_price', 120000)
        ->assertJsonPath('data.pickup_service_area.code', 'HN')
        ->assertJsonPath('data.dropoff_service_area.code', 'HP')
        ->assertJsonCount(2, 'data.stops');

    $routeId = $response->json('data.id');
    expect(Route::findOrFail($routeId)->operator_id)->toBe($operator->id);
    $this->assertDatabaseHas('route_stops', [
        'route_id' => $routeId,
        'address' => 'Hoàn Kiếm, Hà Nội',
        'offset_minutes' => 0,
    ]);
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

it('validate payload cập nhật route và đồng bộ lại service area', function () {
    $operator = makeRouteOperator('A');
    $route = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hà Nội → Hải Phòng',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'base_price' => 120000,
    ]);
    actingAsRouteOperator($operator);

    $this->putJson("/api/operator/routes/{$route->id}", [
        'name' => 'Hải Phòng → Hà Nội',
        'origin_city' => 'Hải Phòng',
        'dest_city' => 'Hà Nội',
        'est_duration_min' => 160,
    ])->assertOk()
        ->assertJsonPath('data.pickup_service_area.code', 'HP')
        ->assertJsonPath('data.dropoff_service_area.code', 'HN');

    expect($route->refresh()->est_duration_min)->toBe(160);
});
