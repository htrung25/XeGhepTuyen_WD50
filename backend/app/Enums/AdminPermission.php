<?php

namespace App\Enums;

/**
 * Danh mục quyền admin (cố định trong code).
 * Vai trò (AdminRole) lưu DB chỉ chứa các key thuộc danh mục này.
 */
enum AdminPermission: string
{
    // Tổng quan
    case DashboardView = 'dashboard.view';

    // Nhà xe
    case OperatorsView = 'operators.view';
    case OperatorsReview = 'operators.review';
    case OperatorsSuspend = 'operators.suspend';
    case OperatorsResetPassword = 'operators.reset_password';

    // Đơn đăng ký đối tác
    case PartnerApplicationsView = 'partner_applications.view';
    case PartnerApplicationsReview = 'partner_applications.review';

    // Tài xế
    case DriversView = 'drivers.view';
    case DriversReview = 'drivers.review';
    case DriversSuspend = 'drivers.suspend';
    case DriversResetPassword = 'drivers.reset_password';

    // Người dùng
    case UsersView = 'users.view';
    case UsersBan = 'users.ban';

    // Đặt vé
    case BookingsView = 'bookings.view';

    // Chuyến đi
    case TripsView = 'trips.view';
    case TripsCancel = 'trips.cancel';
    case TripsAutoResolve = 'trips.auto_resolve';

    // Tài chính
    case FinanceView = 'finance.view';
    case FinancePayout = 'finance.payout';
    case FinanceRefund = 'finance.refund';

    // Voucher
    case VouchersView = 'vouchers.view';
    case VouchersManage = 'vouchers.manage';

    // Nhật ký hệ thống
    case AuditLogsView = 'audit_logs.view';

    // Phân quyền (vai trò)
    case AdminRolesView = 'admin_roles.view';
    case AdminRolesManage = 'admin_roles.manage';

    // Nhân viên admin
    case AdminStaffView = 'admin_staff.view';
    case AdminStaffManage = 'admin_staff.manage';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'Xem tổng quan',
            self::OperatorsView => 'Xem danh sách nhà xe',
            self::OperatorsReview => 'Duyệt/từ chối nhà xe',
            self::OperatorsSuspend => 'Đình chỉ/khôi phục nhà xe',
            self::OperatorsResetPassword => 'Đặt lại mật khẩu nhà xe',
            self::PartnerApplicationsView => 'Xem đơn đăng ký đối tác',
            self::PartnerApplicationsReview => 'Duyệt/từ chối đơn đối tác',
            self::DriversView => 'Xem danh sách tài xế',
            self::DriversReview => 'Duyệt/từ chối tài xế',
            self::DriversSuspend => 'Đình chỉ tài xế',
            self::DriversResetPassword => 'Đặt lại mật khẩu tài xế',
            self::UsersView => 'Xem danh sách người dùng',
            self::UsersBan => 'Khóa/mở khóa người dùng',
            self::BookingsView => 'Xem danh sách đặt vé',
            self::TripsView => 'Xem danh sách chuyến đi',
            self::TripsCancel => 'Hủy chuyến đi',
            self::TripsAutoResolve => 'Xử lý chuyến quá giờ',
            self::FinanceView => 'Xem tài chính',
            self::FinancePayout => 'Quyết toán/chi tiền nhà xe',
            self::FinanceRefund => 'Hoàn tiền thủ công',
            self::VouchersView => 'Xem danh sách voucher',
            self::VouchersManage => 'Tạo/sửa/xóa voucher',
            self::AuditLogsView => 'Xem nhật ký hệ thống',
            self::AdminRolesView => 'Xem vai trò phân quyền',
            self::AdminRolesManage => 'Tạo/sửa/xóa vai trò',
            self::AdminStaffView => 'Xem nhân viên admin',
            self::AdminStaffManage => 'Quản lý nhân viên admin',
        };
    }

    /**
     * Nhóm module của mỗi quyền (để dựng cây checkbox trên UI).
     */
    public function module(): string
    {
        return match ($this) {
            self::DashboardView => 'Tổng quan',
            self::OperatorsView,
            self::OperatorsReview,
            self::OperatorsSuspend,
            self::OperatorsResetPassword => 'Nhà xe',
            self::PartnerApplicationsView,
            self::PartnerApplicationsReview => 'Đơn đối tác',
            self::DriversView,
            self::DriversReview,
            self::DriversSuspend,
            self::DriversResetPassword => 'Tài xế',
            self::UsersView,
            self::UsersBan => 'Người dùng',
            self::BookingsView => 'Đặt vé',
            self::TripsView,
            self::TripsCancel,
            self::TripsAutoResolve => 'Chuyến đi',
            self::FinanceView,
            self::FinancePayout,
            self::FinanceRefund => 'Tài chính',
            self::VouchersView,
            self::VouchersManage => 'Voucher',
            self::AuditLogsView => 'Nhật ký hệ thống',
            self::AdminRolesView,
            self::AdminRolesManage => 'Phân quyền',
            self::AdminStaffView,
            self::AdminStaffManage => 'Nhân viên admin',
        };
    }

    /** Tất cả key quyền (mảng string). */
    public static function values(): array
    {
        return array_map(fn (self $p): string => $p->value, self::cases());
    }

    /**
     * Catalog gom theo module cho UI:
     * [ ['module' => 'Nhà xe', 'permissions' => [['key' => ..., 'label' => ...], ...]], ... ]
     */
    public static function catalog(): array
    {
        $groups = [];
        foreach (self::cases() as $permission) {
            $groups[$permission->module()][] = [
                'key' => $permission->value,
                'label' => $permission->label(),
            ];
        }

        return array_map(
            fn (string $module, array $permissions): array => [
                'module' => $module,
                'permissions' => $permissions,
            ],
            array_keys($groups),
            $groups,
        );
    }
}
