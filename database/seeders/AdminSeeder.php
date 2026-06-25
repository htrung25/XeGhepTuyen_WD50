<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@xeghep.vn'],
            [
                'full_name'   => 'Super Admin',
                'phone'       => '0900000000',
                'password'    => Hash::make('Admin@123456'),
                'role'        => UserRole::Admin,
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
    }
}
