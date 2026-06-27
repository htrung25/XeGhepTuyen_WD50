<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superRole = AdminRole::where('slug', 'super-admin')->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@xeghep.vn'],
            [
                'full_name'     => 'Super Admin',
                'phone'         => '0900000000',
                'password'      => Hash::make('Admin@123456'),
                'role'          => UserRole::Admin,
                'admin_role_id' => $superRole?->id,
                'is_verified'   => true,
                'is_active'     => true,
            ]
        );

        // Đảm bảo admin mặc định luôn gắn vai trò Super Admin (kể cả khi đã tồn tại)
        if ($superRole && $admin->admin_role_id !== $superRole->id) {
            $admin->update(['admin_role_id' => $superRole->id]);
        }
    }
}
