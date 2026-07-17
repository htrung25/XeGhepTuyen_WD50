<?php

namespace Database\Seeders;

use App\Enums\AdminPermissionEnum as P;
use App\Models\AdminRole;
use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Vai trò hệ thống — toàn quyền, không cho xóa/sửa quyền
        AdminRole::updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Toàn quyền hệ thống',
                'permissions' => P::values(),
                'is_super' => true,
                'is_system' => true,
            ]
        );

        // Các vai trò mẫu (admin có thể sửa/xóa sau)
        $this->preset('quan-ly-van-hanh', 'Quản lý vận hành', 'Duyệt nhà xe/tài xế/đối tác và quản lý chuyến đi', [
            P::DashboardView,
            P::OperatorsView, P::OperatorsReview, P::OperatorsSuspend, P::OperatorsResetPassword,
            P::PartnerApplicationsView, P::PartnerApplicationsReview,
            P::DriversView, P::DriversReview, P::DriversSuspend, P::DriversResetPassword,
            P::TripsView, P::TripsCancel, P::TripsAutoResolve,
            P::BookingsView,
        ]);

        $this->preset('ke-toan', 'Kế toán', 'Xem tài chính và thực hiện quyết toán nhà xe', [
            P::DashboardView,
            P::FinanceView, P::FinancePayout,
            P::BookingsView,
        ]);

        $this->preset('cskh', 'Chăm sóc khách hàng', 'Quản lý người dùng, đặt vé, chuyến đi và yêu cầu hỗ trợ', [
            P::DashboardView,
            P::UsersView, P::UsersBan,
            P::BookingsView,
            P::TripsView,
            P::SupportTicketsView, P::SupportTicketsManage,
        ], requiredPermissions: [
            P::SupportTicketsView, P::SupportTicketsManage,
        ]);

        $this->preset('kiem-soat', 'Kiểm soát', 'Chỉ xem nhật ký hệ thống và tổng quan', [
            P::DashboardView,
            P::AuditLogsView,
        ]);
    }

    /**
     * @param  array<int, P>  $permissions
     * @param  array<int, P>  $requiredPermissions  Quyền mới bắt buộc được merge, không ghi đè tùy chỉnh production.
     */
    private function preset(
        string $slug,
        string $name,
        string $description,
        array $permissions,
        array $requiredPermissions = [],
    ): void {
        $role = AdminRole::firstOrNew(['slug' => $slug]);
        $defaultKeys = array_map(fn (P $permission): string => $permission->value, $permissions);
        $requiredKeys = array_map(fn (P $permission): string => $permission->value, $requiredPermissions);

        $role->fill([
            'name' => $name,
            'description' => $description,
            'permissions' => $role->exists
                ? array_values(array_unique([...($role->permissions ?? []), ...$requiredKeys]))
                : $defaultKeys,
            'is_super' => false,
            'is_system' => false,
        ])->save();
    }
}
