<?php

namespace Database\Seeders;

use App\Enums\OperatorStatus;
use App\Enums\UserRole;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        $operatorData = [
            [
                'user'     => [
                    'full_name' => 'Nhà xe Hoàng Long',
                    'email'     => 'operator@hoanglongbus.vn',
                    'phone'     => '0901234567',
                    'password'  => Hash::make('Operator@123456'),
                    'role'      => UserRole::Operator,
                    'is_verified' => true,
                    'is_active'   => true,
                ],
                'operator' => [
                    'company_name'     => 'Nhà xe Hoàng Long',
                    'tax_code'         => '0123456789',
                    'business_license' => 'GP-VCHK-001234',
                    'status'           => OperatorStatus::Verified,
                    'commission_rate'  => 5.00,
                    'verified_at'      => now(),
                ],
            ],
            [
                'user'     => [
                    'full_name' => 'Nhà xe Kumho Samco',
                    'email'     => 'operator@kumhosamco.vn',
                    'phone'     => '0912345678',
                    'password'  => Hash::make('Operator@123456'),
                    'role'      => UserRole::Operator,
                    'is_verified' => true,
                    'is_active'   => true,
                ],
                'operator' => [
                    'company_name'     => 'Kumho Samco Express',
                    'tax_code'         => '0987654321',
                    'business_license' => 'GP-VCHK-009876',
                    'status'           => OperatorStatus::Verified,
                    'commission_rate'  => 5.00,
                    'verified_at'      => now(),
                ],
            ],
        ];

        foreach ($operatorData as $data) {
            $user = User::firstOrCreate(['email' => $data['user']['email']], $data['user']);
            Operator::firstOrCreate(['user_id' => $user->id], array_merge($data['operator'], ['user_id' => $user->id]));
        }
    }
}
