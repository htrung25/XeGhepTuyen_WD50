<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state — khớp schema bảng `users` của dự án
     * (full_name/phone/role), KHÔNG dùng cột starter name/email_verified_at.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Customer,
            'is_verified' => true,
            'is_active' => true,
        ];
    }

    /**
     * Tài khoản chưa xác thực SĐT (OTP).
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function driver(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Driver]);
    }

    public function operator(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Operator]);
    }
}
