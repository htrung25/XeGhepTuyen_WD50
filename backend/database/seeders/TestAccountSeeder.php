<?php

namespace Database\Seeders;

use App\Enums\DriverStatusEnum;
use App\Enums\OperatorStatusEnum;
use App\Enums\UserRoleEnum;
use App\Enums\VehicleStatusEnum;
use App\Enums\VehicleTypeEnum;
use App\Models\AdminRole;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestAccountSeeder extends Seeder
{
    public const PASSWORD = 'Test@123456';

    public const CUSTOMER_PHONE = '0900000091';

    public const DRIVER_PHONE = '0900000092';

    public const OPERATOR_PHONE = '0900000093';

    public const ADMIN_EMAIL = 'test.admin@xeghep.vn';

    public function run(): void
    {
        $this->call(AdminRoleSeeder::class);

        DB::transaction(function (): void {
            $customer = $this->upsertUser(
                phone: self::CUSTOMER_PHONE,
                email: 'test.customer@xeghep.vn',
                role: UserRoleEnum::Customer,
                fullName: 'Test Account - Khách hàng',
            );

            Wallet::updateOrCreate(
                ['user_id' => $customer->id],
                ['balance' => 5_000_000, 'pending_balance' => 0],
            );

            $operatorUser = $this->upsertUser(
                phone: self::OPERATOR_PHONE,
                email: 'test.operator@xeghep.vn',
                role: UserRoleEnum::Operator,
                fullName: 'Test Account - Nhà xe',
            );

            $operator = Operator::withTrashed()->firstOrNew(['user_id' => $operatorUser->id]);
            $operator->fill([
                'company_name' => 'Nhà xe Test Account',
                'business_license' => 'TEST-BUSINESS-LICENSE',
                'tax_code' => 'TEST000001',
                'bank_account' => '0000000001',
                'bank_name' => 'Ngân hàng Test',
                'bank_account_name' => 'TEST ACCOUNT',
                'commission_rate' => 5,
                'description' => 'Dữ liệu phục vụ kiểm thử toàn bộ chức năng nhà xe.',
                'status' => OperatorStatusEnum::Verified,
                'verified_at' => now(),
            ]);
            $operator->deleted_at = null;
            $operator->save();

            $vehicle = Vehicle::withTrashed()->firstOrNew(['plate_number' => 'TEST-0001']);
            $vehicle->fill([
                'operator_id' => $operator->id,
                'brand' => 'Ford',
                'model' => 'Transit Test',
                'color' => 'Trắng',
                'year' => now()->year,
                'vehicle_type' => VehicleTypeEnum::Van9,
                'seat_count' => 9,
                'registration_number' => 'TEST-REG-0001',
                'registration_expiry' => now()->addYears(2),
                'insurance_expiry' => now()->addYear(),
                'amenities' => ['wifi', 'usb', 'air_conditioner'],
                'status' => VehicleStatusEnum::Active,
            ]);
            $vehicle->deleted_at = null;
            $vehicle->save();

            $driverUser = $this->upsertUser(
                phone: self::DRIVER_PHONE,
                email: 'test.driver@xeghep.vn',
                role: UserRoleEnum::Driver,
                fullName: 'Test Account - Tài xế',
            );

            $driver = Driver::withTrashed()->firstOrNew(['user_id' => $driverUser->id]);
            $driver->fill([
                'operator_id' => $operator->id,
                'current_vehicle_id' => $vehicle->id,
                'license_number' => 'TEST-LICENSE-0001',
                'license_class' => 'D',
                'license_expiry' => now()->addYears(5),
                'id_card_number' => '000000000091',
                'rating_avg' => 5,
                'total_trips' => 0,
                'is_online' => false,
                'status' => DriverStatusEnum::Verified,
                'verified_at' => now(),
                'reject_reason' => null,
            ]);
            $driver->deleted_at = null;
            $driver->save();

            $superAdminRole = AdminRole::where('slug', 'super-admin')->firstOrFail();
            $admin = $this->upsertUser(
                phone: '0900000094',
                email: self::ADMIN_EMAIL,
                role: UserRoleEnum::Admin,
                fullName: 'Test Account - Quản trị viên',
            );
            $admin->update(['admin_role_id' => $superAdminRole->id]);
        });

        $this->command?->info('Đã tạo bộ Test Account cho customer, driver, operator và admin.');
    }

    private function upsertUser(
        string $phone,
        string $email,
        UserRoleEnum $role,
        string $fullName,
    ): User {
        $user = User::withTrashed()->firstOrNew(['phone' => $phone]);
        $user->fill([
            'full_name' => $fullName,
            'email' => $email,
            'password' => self::PASSWORD,
            'role' => $role,
            'is_verified' => true,
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $user->deleted_at = null;
        $user->save();

        return $user;
    }
}
