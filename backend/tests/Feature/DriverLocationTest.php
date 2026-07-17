<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;

/**
 * §4.4: GPS tài xế tối đa 1 lần / 10 giây (Redis rate-limit) — chống spam server.
 */
it('chặn cập nhật GPS quá 1 lần trong 10 giây (429)', function () {
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP', 'status' => 'verified',
    ]);
    $user = User::factory()->create(['role' => UserRoleEnum::Driver]);
    Driver::create([
        'user_id' => $user->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);

    // LocationController dùng auth('driver') → cần token thật.
    $headers = ['Authorization' => 'Bearer '.$user->createToken('driver_token')->plainTextToken];
    $payload = ['lat' => 21.0, 'lng' => 105.8];

    $this->postJson('/api/driver/location', $payload, $headers)->assertOk();
    // Lần 2 trong cùng 10 giây → bị chặn.
    $this->postJson('/api/driver/location', $payload, $headers)->assertStatus(429);
});
