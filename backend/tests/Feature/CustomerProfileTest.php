<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns real customer profile metadata', function () {
    $customer = User::factory()->create([
        'role' => UserRoleEnum::Customer,
        'created_at' => now()->subYear(),
    ]);
    Sanctum::actingAs($customer);

    $this->getJson('/api/customer/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $customer->id)
        ->assertJsonPath('data.total_trips', 0)
        ->assertJsonPath('data.created_at', fn ($value) => is_string($value));
});

it('persists customer profile changes', function () {
    $customer = User::factory()->create([
        'role' => UserRoleEnum::Customer,
        'email' => 'old@example.com',
    ]);
    Sanctum::actingAs($customer);

    $this->putJson('/api/customer/auth/profile', [
        'full_name' => 'Khách Hàng Mới',
        'email' => 'new@example.com',
    ])->assertOk()
        ->assertJsonPath('data.full_name', 'Khách Hàng Mới')
        ->assertJsonPath('data.email', 'new@example.com');

    expect($customer->refresh())
        ->full_name->toBe('Khách Hàng Mới')
        ->email->toBe('new@example.com');
});

it('rejects an email already used by another account', function () {
    User::factory()->create(['email' => 'used@example.com']);
    $customer = User::factory()->create(['role' => UserRoleEnum::Customer]);
    Sanctum::actingAs($customer);

    $this->putJson('/api/customer/auth/profile', [
        'email' => 'used@example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});
