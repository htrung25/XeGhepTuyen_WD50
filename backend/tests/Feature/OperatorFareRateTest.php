<?php

use App\Enums\UserRoleEnum;
use App\Models\Operator;
use App\Models\Route;
use App\Models\User;
use App\Services\FarePricingService;
use Laravel\Sanctum\Sanctum;

function makeFareOperator(string $suffix = 'A'): Operator
{
    $user = User::factory()->create(['role' => UserRoleEnum::Operator]);

    $operator = Operator::create([
        'user_id' => $user->id,
        'company_name' => "Nhà xe giá vé {$suffix}",
        'business_license' => 'GP-FARE-'.$suffix,
        'status' => 'verified',
    ]);

    Sanctum::actingAs($user, ['*'], 'sanctum');
    Sanctum::actingAs($user, ['*'], 'operator');

    return $operator;
}

function makeFareRoute(Operator $operator, string $name = 'Hà Nội → Hải Phòng', int $km = 100): Route
{
    return $operator->routes()->create([
        'name' => $name,
        'origin_city' => 'Hà Nội',
        'origin_district' => 'Quận Ba Đình',
        'dest_city' => 'Hải Phòng',
        'dest_district' => 'Quận Hồng Bàng',
        'distance_km' => $km,
        'est_duration_min' => 150,
        'base_price' => 0,
    ]);
}

it('liệt kê tuyến của nhà xe kèm trạng thái đã gán giá hay chưa', function () {
    $operator = makeFareOperator();
    $unpriced = makeFareRoute($operator, 'Chưa có giá');
    $priced = makeFareRoute($operator, 'Đã có giá', 120);
    $operator->fareRates()->create([
        'route_id' => $priced->id, 'base_fare' => 0, 'price_per_km' => 1000,
    ]);

    $response = $this->getJson('/api/operator/fare-rates')
        ->assertOk()
        ->assertJsonCount(2, 'data.routes')
        ->assertJsonPath('data.rounding_step', FarePricingService::ROUNDING_STEP);

    $rows = collect($response->json('data.routes'))->keyBy('id');
    expect($rows[$unpriced->id]['price_per_km'])->toBeNull()
        ->and((float) $rows[$priced->id]['price_per_km'])->toBe(1000.0)
        ->and($rows[$priced->id]['distance_km'])->toBe(120);
});

it('lưu đơn giá cho tuyến và tính lại giá vé của tuyến đó', function () {
    $operator = makeFareOperator();
    $route = makeFareRoute($operator, 'HN → HP', 100);

    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['route_id' => $route->id, 'base_fare' => 20000, 'price_per_km' => 1500],
    ]])->assertOk()->assertJsonPath('success', true);

    // 20.000 + 100 × 1.500 = 170.000
    expect((int) $route->refresh()->base_price)->toBe(170000);
    $this->assertDatabaseHas('operator_fare_rates', [
        'operator_id' => $operator->id,
        'route_id' => $route->id,
        'price_per_km' => 1500,
    ]);
});

it('từ chối gán giá cho tuyến của nhà xe khác', function () {
    $other = makeFareOperator('B');
    $foreignRoute = makeFareRoute($other, 'Tuyến nhà xe khác');
    makeFareOperator('A'); // đăng nhập lại bằng nhà xe A

    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['route_id' => $foreignRoute->id, 'base_fare' => 0, 'price_per_km' => 1000],
    ]])->assertStatus(422)->assertJsonValidationErrors('rates.0.route_id');
});

it('từ chối hai dòng giá cho cùng một tuyến', function () {
    $operator = makeFareOperator();
    $route = makeFareRoute($operator);

    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['route_id' => $route->id, 'base_fare' => 0, 'price_per_km' => 1000],
        ['route_id' => $route->id, 'base_fare' => 0, 'price_per_km' => 2000],
    ]])->assertStatus(422)->assertJsonValidationErrors('rates.1.route_id');
});

it('công khai danh mục 63 tỉnh kèm huyện', function () {
    $this->getJson('/api/public/provinces')
        ->assertOk()
        ->assertJsonCount(63, 'data')
        ->assertJsonPath('data.0.name', 'Hà Nội');
});
