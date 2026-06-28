<?php

use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\PartnerApplicationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes  (prefix: /api/admin)
|--------------------------------------------------------------------------
| Quyền chi tiết (RBAC) gắn qua middleware `permission:<key>` — xem
| App\Enums\AdminPermission. Group đã có `role:admin` cô lập portal.
*/

// Auth — unauthenticated
Route::post('auth/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Hồ sơ cá nhân — mọi admin đều được, không cần quyền riêng
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view');
    Route::get('dashboard/map', [DashboardController::class, 'map'])->middleware('permission:dashboard.view');

    // Operators
    Route::get('operators', [OperatorController::class, 'index'])->middleware('permission:operators.view');
    Route::get('operators/{id}', [OperatorController::class, 'show'])->middleware('permission:operators.view');
    Route::post('operators/{id}/approve', [OperatorController::class, 'approve'])->middleware('permission:operators.review');
    Route::post('operators/{id}/reject', [OperatorController::class, 'reject'])->middleware('permission:operators.review');
    Route::post('operators/{id}/suspend', [OperatorController::class, 'suspend'])->middleware('permission:operators.suspend');
    Route::post('operators/{id}/restore', [OperatorController::class, 'restore'])->middleware('permission:operators.suspend');
    Route::post('operators/{id}/reset-password', [OperatorController::class, 'resetPassword'])->middleware('permission:operators.reset_password');

    // Đơn đăng ký đối tác (chờ duyệt nhà xe)
    Route::get('partner-applications', [PartnerApplicationController::class, 'index'])->middleware('permission:partner_applications.view');
    Route::post('partner-applications/{id}/approve', [PartnerApplicationController::class, 'approve'])->middleware('permission:partner_applications.review');
    Route::post('partner-applications/{id}/reject', [PartnerApplicationController::class, 'reject'])->middleware('permission:partner_applications.review');

    // Drivers
    Route::get('drivers', [DriverController::class, 'index'])->middleware('permission:drivers.view');
    Route::get('drivers/{id}', [DriverController::class, 'show'])->middleware('permission:drivers.view');
    Route::post('drivers/{id}/approve', [DriverController::class, 'approve'])->middleware('permission:drivers.review');
    Route::post('drivers/{id}/reject', [DriverController::class, 'reject'])->middleware('permission:drivers.review');
    Route::post('drivers/{id}/suspend', [DriverController::class, 'suspend'])->middleware('permission:drivers.suspend');
    Route::post('drivers/{id}/reset-password', [DriverController::class, 'resetPassword'])->middleware('permission:drivers.reset_password');

    // Users
    Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::get('users/{id}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::post('users/{id}/ban', [UserController::class, 'ban'])->middleware('permission:users.ban');
    Route::post('users/{id}/unban', [UserController::class, 'unban'])->middleware('permission:users.ban');

    // Bookings
    Route::get('bookings', [BookingController::class, 'index'])->middleware('permission:bookings.view');

    // Trips
    Route::get('trips', [TripController::class, 'index'])->middleware('permission:trips.view');
    Route::post('trips/auto-resolve', [TripController::class, 'autoResolve'])->middleware('permission:trips.auto_resolve');
    Route::get('trips/monitor', [TripController::class, 'monitor'])->middleware('permission:trips.view');
    Route::get('trips/{id}', [TripController::class, 'show'])->middleware('permission:trips.view');
    Route::post('trips/{id}/cancel', [TripController::class, 'cancel'])->middleware('permission:trips.cancel');

    // Finance
    Route::get('finance/summary', [FinanceController::class, 'summary'])->middleware('permission:finance.view');
    Route::get('finance/transactions', [FinanceController::class, 'transactions'])->middleware('permission:finance.view');
    Route::get('finance/refunds', [FinanceController::class, 'refunds'])->middleware('permission:finance.view');
    Route::get('finance/commissions', [FinanceController::class, 'commissions'])->middleware('permission:finance.view');
    Route::get('finance/payouts', [FinanceController::class, 'payouts'])->middleware('permission:finance.view');
    Route::post('finance/payouts', [FinanceController::class, 'payout'])->middleware('permission:finance.payout');
    Route::post('finance/refund/{booking}', [FinanceController::class, 'refund'])->middleware('permission:finance.refund');

    // Vouchers
    Route::get('vouchers', [VoucherController::class, 'index'])->middleware('permission:vouchers.view');
    Route::post('vouchers', [VoucherController::class, 'store'])->middleware('permission:vouchers.manage');
    Route::get('vouchers/{id}', [VoucherController::class, 'show'])->middleware('permission:vouchers.view');
    Route::put('vouchers/{id}', [VoucherController::class, 'update'])->middleware('permission:vouchers.manage');
    Route::put('vouchers/{id}/toggle', [VoucherController::class, 'toggle'])->middleware('permission:vouchers.manage');
    Route::delete('vouchers/{id}', [VoucherController::class, 'destroy'])->middleware('permission:vouchers.manage');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view');
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->middleware('permission:audit_logs.view');

    // Phân quyền — vai trò admin
    Route::get('roles/permissions', [RoleController::class, 'permissions'])->middleware('permission:admin_roles.view');
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:admin_roles.view');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:admin_roles.manage');
    Route::get('roles/{id}', [RoleController::class, 'show'])->middleware('permission:admin_roles.view');
    Route::put('roles/{id}', [RoleController::class, 'update'])->middleware('permission:admin_roles.manage');
    Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:admin_roles.manage');

    // Nhân viên admin
    Route::get('admin-staff', [AdminStaffController::class, 'index'])->middleware('permission:admin_staff.view');
    Route::post('admin-staff', [AdminStaffController::class, 'store'])->middleware('permission:admin_staff.manage');
    Route::get('admin-staff/{id}', [AdminStaffController::class, 'show'])->middleware('permission:admin_staff.view');
    Route::put('admin-staff/{id}', [AdminStaffController::class, 'update'])->middleware('permission:admin_staff.manage');
    Route::post('admin-staff/{id}/ban', [AdminStaffController::class, 'ban'])->middleware('permission:admin_staff.manage');
    Route::post('admin-staff/{id}/unban', [AdminStaffController::class, 'unban'])->middleware('permission:admin_staff.manage');
    Route::post('admin-staff/{id}/reset-password', [AdminStaffController::class, 'resetPassword'])->middleware('permission:admin_staff.manage');
});
