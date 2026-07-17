<?php

use App\Enums\DriverStatusEnum;
use App\Enums\OperatorStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;
use Database\Seeders\TestAccountSeeder;
use Illuminate\Support\Facades\Hash;

it('tạo đủ bốn test account với trạng thái và quan hệ cần thiết', function () {
    $this->seed(TestAccountSeeder::class);

    $customer = User::where('phone', TestAccountSeeder::CUSTOMER_PHONE)->firstOrFail();
    $driver = User::where('phone', TestAccountSeeder::DRIVER_PHONE)->firstOrFail();
    $operator = User::where('phone', TestAccountSeeder::OPERATOR_PHONE)->firstOrFail();
    $admin = User::where('email', TestAccountSeeder::ADMIN_EMAIL)->firstOrFail();

    expect($customer->role)->toBe(UserRoleEnum::Customer)
        ->and($customer->wallet->balance)->toBe(5_000_000)
        ->and($driver->role)->toBe(UserRoleEnum::Driver)
        ->and($driver->driver->status)->toBe(DriverStatusEnum::Verified)
        ->and($driver->driver->currentVehicle)->not->toBeNull()
        ->and($operator->role)->toBe(UserRoleEnum::Operator)
        ->and($operator->operator->status)->toBe(OperatorStatusEnum::Verified)
        ->and($operator->operator->vehicles)->toHaveCount(1)
        ->and($admin->role)->toBe(UserRoleEnum::Admin)
        ->and($admin->isSuperAdmin())->toBeTrue()
        ->and(Hash::check(TestAccountSeeder::PASSWORD, $customer->password))->toBeTrue()
        ->and(Hash::check(TestAccountSeeder::PASSWORD, $driver->password))->toBeTrue()
        ->and(Hash::check(TestAccountSeeder::PASSWORD, $operator->password))->toBeTrue()
        ->and(Hash::check(TestAccountSeeder::PASSWORD, $admin->password))->toBeTrue();
});

it('cho phép cả bốn test account đăng nhập vào đúng portal', function () {
    $this->seed(TestAccountSeeder::class);

    $this->postJson('/api/customer/auth/login', [
        'phone' => TestAccountSeeder::CUSTOMER_PHONE,
        'password' => TestAccountSeeder::PASSWORD,
    ])->assertOk()->assertJsonStructure(['data' => ['token']]);

    $this->postJson('/api/driver/auth/login', [
        'phone' => TestAccountSeeder::DRIVER_PHONE,
        'password' => TestAccountSeeder::PASSWORD,
    ])->assertOk()->assertJsonStructure(['data' => ['token']]);

    $this->postJson('/api/operator/auth/login', [
        'phone' => TestAccountSeeder::OPERATOR_PHONE,
        'password' => TestAccountSeeder::PASSWORD,
    ])->assertOk()->assertJsonStructure(['data' => ['token']]);

    $this->postJson('/api/admin/auth/login', [
        'email' => TestAccountSeeder::ADMIN_EMAIL,
        'password' => TestAccountSeeder::PASSWORD,
    ])->assertOk()
        ->assertJsonPath('data.user.is_super', true)
        ->assertJsonStructure(['data' => ['token']]);
});

it('có thể chạy lại seeder mà không nhân đôi test account', function () {
    $this->seed(TestAccountSeeder::class);
    $this->seed(TestAccountSeeder::class);

    expect(User::whereIn('phone', [
        TestAccountSeeder::CUSTOMER_PHONE,
        TestAccountSeeder::DRIVER_PHONE,
        TestAccountSeeder::OPERATOR_PHONE,
        '0900000094',
    ])->count())->toBe(4);
});
