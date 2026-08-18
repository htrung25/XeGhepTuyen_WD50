<?php

use App\Enums\UserRoleEnum;
use App\Models\Operator;
use App\Models\User;
use App\Models\Voucher;

it('trả voucher còn hiệu lực từ cơ sở dữ liệu kèm phạm vi nhà xe', function () {
    $operator = Operator::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Operator])->id,
        'company_name' => 'Nhà xe Cánh Én',
        'business_license' => 'GP-PUBLIC-VOUCHER',
        'status' => 'verified',
    ]);

    $active = Voucher::create([
        'code' => 'DBACTIVE20',
        'operator_id' => $operator->id,
        'discount_type' => 'percent',
        'discount_value' => 20,
        'min_order' => 100000,
        'max_discount' => 50000,
        'usage_limit' => 10,
        'used_count' => 2,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDays(10),
        'is_active' => true,
    ]);

    Voucher::create([
        'code' => 'DBEXPIRED',
        'discount_type' => 'fixed',
        'discount_value' => 20000,
        'min_order' => 0,
        'usage_limit' => 10,
        'used_count' => 0,
        'valid_from' => now()->subDays(10),
        'valid_until' => now()->subDay(),
        'is_active' => true,
    ]);

    Voucher::create([
        'code' => 'DBEXHAUSTED',
        'discount_type' => 'fixed',
        'discount_value' => 10000,
        'min_order' => 0,
        'usage_limit' => 5,
        'used_count' => 5,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDays(20),
        'is_active' => true,
    ]);

    $this->getJson('/api/public/vouchers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id)
        ->assertJsonPath('data.0.code', 'DBACTIVE20')
        ->assertJsonPath('data.0.discount_type', 'percent')
        ->assertJsonPath('data.0.operator.id', $operator->id)
        ->assertJsonPath('data.0.operator.company_name', 'Nhà xe Cánh Én')
        ->assertJsonMissing(['code' => 'DBEXPIRED'])
        ->assertJsonMissing(['code' => 'DBEXHAUSTED']);
});
