<?php

use App\Enums\UserRoleEnum;
use App\Models\Operator;
use App\Models\User;
use App\Services\FarePricingService;
use Laravel\Sanctum\Sanctum;

function makeFareOperator(): Operator
{
    $user = User::factory()->create(['role' => UserRoleEnum::Operator]);

    $operator = Operator::create([
        'user_id' => $user->id,
        'company_name' => 'Nhà xe giá vé',
        'business_license' => 'GP-FARE',
        'status' => 'verified',
    ]);

    Sanctum::actingAs($user, ['*'], 'sanctum');
    Sanctum::actingAs($user, ['*'], 'operator');

    return $operator;
}

it('trả bảng giá rỗng kèm tham số tính giá khi chưa cấu hình', function () {
    makeFareOperator();

    $this->getJson('/api/operator/fare-rates')
        ->assertOk()
        ->assertJsonCount(0, 'data.rates')
        ->assertJsonPath('data.rounding_step', FarePricingService::ROUNDING_STEP);
});

it('lưu bảng giá theo huyện và thay thế toàn bộ dòng cũ', function () {
    $operator = makeFareOperator();
    $operator->fareRates()->create([
        'province_code' => null, 'district_code' => null,
        'base_fare' => 1000, 'price_per_km' => 900,
    ]);

    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['province_code' => null, 'district_code' => null, 'base_fare' => 0, 'price_per_km' => 500],
        ['province_code' => '01', 'district_code' => '001', 'base_fare' => 10000, 'price_per_km' => 2000],
    ]])->assertOk()->assertJsonPath('success', true);

    expect($operator->fareRates()->count())->toBe(2);
    $this->assertDatabaseHas('operator_fare_rates', [
        'operator_id' => $operator->id,
        'district_code' => '001',
        'district_name' => 'Quận Ba Đình',
        'province_name' => 'Hà Nội',
    ]);
});

it('từ chối dòng giá sai danh mục hoặc trùng khu vực', function () {
    makeFareOperator();

    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['province_code' => '99', 'district_code' => null, 'base_fare' => 0, 'price_per_km' => 500],
    ]])->assertStatus(422)->assertJsonValidationErrors('rates.0.province_code');

    // Huyện không thuộc tỉnh đã chọn
    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['province_code' => '01', 'district_code' => '303', 'base_fare' => 0, 'price_per_km' => 500],
    ]])->assertStatus(422)->assertJsonValidationErrors('rates.0.district_code');

    // Chọn huyện mà bỏ trống tỉnh
    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['province_code' => null, 'district_code' => '001', 'base_fare' => 0, 'price_per_km' => 500],
    ]])->assertStatus(422)->assertJsonValidationErrors('rates.0.province_code');

    // Trùng khu vực trong cùng payload
    $this->putJson('/api/operator/fare-rates', ['rates' => [
        ['province_code' => '01', 'district_code' => '001', 'base_fare' => 0, 'price_per_km' => 500],
        ['province_code' => '01', 'district_code' => '001', 'base_fare' => 0, 'price_per_km' => 900],
    ]])->assertStatus(422)->assertJsonValidationErrors('rates.1.district_code');
});

it('công khai danh mục 63 tỉnh kèm huyện', function () {
    $this->getJson('/api/public/provinces')
        ->assertOk()
        ->assertJsonCount(63, 'data')
        ->assertJsonPath('data.0.name', 'Hà Nội');
});
