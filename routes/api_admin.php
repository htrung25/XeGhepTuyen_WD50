<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\PartnerApplicationController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes  (prefix: /api/admin)
|--------------------------------------------------------------------------
*/

// Auth — unauthenticated
Route::post('auth/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('dashboard/map', [DashboardController::class, 'map']);

    // Operators
    Route::get('operators', [OperatorController::class, 'index']);
    Route::get('operators/{id}', [OperatorController::class, 'show']);
    Route::post('operators/{id}/approve', [OperatorController::class, 'approve']);
    Route::post('operators/{id}/reject', [OperatorController::class, 'reject']);
    Route::post('operators/{id}/suspend', [OperatorController::class, 'suspend']);
    Route::post('operators/{id}/restore', [OperatorController::class, 'restore']);
    Route::post('operators/{id}/reset-password', [OperatorController::class, 'resetPassword']);

    // Đơn đăng ký đối tác (chờ duyệt nhà xe)
    Route::get('partner-applications', [PartnerApplicationController::class, 'index']);
    Route::post('partner-applications/{id}/approve', [PartnerApplicationController::class, 'approve']);
    Route::post('partner-applications/{id}/reject', [PartnerApplicationController::class, 'reject']);

    // Drivers
    Route::get('drivers', [DriverController::class, 'index']);
    Route::get('drivers/{id}', [DriverController::class, 'show']);
    Route::post('drivers/{id}/approve', [DriverController::class, 'approve']);
    Route::post('drivers/{id}/reject', [DriverController::class, 'reject']);
    Route::post('drivers/{id}/suspend', [DriverController::class, 'suspend']);
    Route::post('drivers/{id}/reset-password', [DriverController::class, 'resetPassword']);

    // Users
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('users/{id}/ban', [UserController::class, 'ban']);
    Route::post('users/{id}/unban', [UserController::class, 'unban']);

    // Trips
    // Bookings
    Route::get('bookings', [BookingController::class, 'index']);

    Route::get('trips', [TripController::class, 'index']);
    Route::post('trips/auto-resolve', [TripController::class, 'autoResolve']);
    Route::get('trips/monitor', [TripController::class, 'monitor']);
    Route::get('trips/{id}', [TripController::class, 'show']);
    Route::post('trips/{id}/cancel', [TripController::class, 'cancel']);

    // Finance
    Route::get('finance/summary', [FinanceController::class, 'summary']);
    Route::get('finance/transactions', [FinanceController::class, 'transactions']);
    Route::get('finance/refunds', [FinanceController::class, 'refunds']);
    Route::get('finance/commissions', [FinanceController::class, 'commissions']);
    Route::post('finance/payouts', [FinanceController::class, 'payout']);

    // Vouchers
    Route::get('vouchers', [VoucherController::class, 'index']);
    Route::post('vouchers', [VoucherController::class, 'store']);
    Route::get('vouchers/{id}', [VoucherController::class, 'show']);
    Route::put('vouchers/{id}', [VoucherController::class, 'update']);
    Route::put('vouchers/{id}/toggle', [VoucherController::class, 'toggle']);
    Route::delete('vouchers/{id}', [VoucherController::class, 'destroy']);

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{id}', [AuditLogController::class, 'show']);
});
