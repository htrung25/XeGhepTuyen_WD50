<?php

use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\NotificationController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\TrackingController;
use App\Http\Controllers\Customer\TripSearchController;
use App\Http\Controllers\Customer\VoucherController;
use App\Http\Controllers\Customer\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer API Routes  (prefix: /api/customer)
|--------------------------------------------------------------------------
*/

// Auth — unauthenticated
Route::post('auth/send-otp',   [AuthController::class, 'sendOtp']);
Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('auth/register',   [AuthController::class, 'register']);
Route::post('auth/login',      [AuthController::class, 'login']);

// Payment callbacks — no auth (gateway posts here)
Route::post('payments/momo/callback',   [PaymentController::class, 'momoCallback']);
Route::post('payments/vnpay/callback',  [PaymentController::class, 'vnpayCallback']);

// Public trip tracking by code
Route::get('trips/{trackingCode}/track', [TrackingController::class, 'trackByCode']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('auth/me',              [AuthController::class, 'me']);
    Route::put('auth/profile',         [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password',[AuthController::class, 'changePassword']);
    Route::post('auth/logout',         [AuthController::class, 'logout']);

    // Trip search
    Route::get('trips',            [TripSearchController::class, 'search']);
    Route::get('trips/{id}',       [TripSearchController::class, 'show']);
    Route::get('trips/{id}/seats', [TripSearchController::class, 'seats']);

    // Bookings
    Route::post('bookings/lock-seats', [BookingController::class, 'lockSeats']);
    Route::get('bookings',             [BookingController::class, 'index']);
    Route::post('bookings',            [BookingController::class, 'store']);
    Route::get('bookings/{id}',        [BookingController::class, 'show']);
    Route::post('bookings/{id}/cancel',[BookingController::class, 'cancel']);
    Route::get('bookings/{id}/qr',     [BookingController::class, 'qr']);
    Route::get('bookings/{id}/track',  [TrackingController::class, 'trackByBooking']);

    // Payments
    Route::post('payments/initiate',          [PaymentController::class, 'initiate']);
    Route::get('payments/{bookingId}/status', [PaymentController::class, 'status']);
    Route::get('wallet',                      [PaymentController::class, 'wallet']);

    // Wallet
    Route::get('wallet/balance',       [WalletController::class, 'balance']);
    Route::get('wallet/transactions',  [WalletController::class, 'transactions']);
    Route::post('wallet/topup',        [WalletController::class, 'topup']);

    // Vouchers
    Route::post('vouchers/apply', [VoucherController::class, 'apply']);

    // Reviews
    Route::post('reviews', [ReviewController::class, 'store']);

    // Notifications
    Route::get('notifications',                    [NotificationController::class, 'index']);
    Route::put('notifications/{id}/read',          [NotificationController::class, 'markRead']);
    Route::put('notifications/read-all',           [NotificationController::class, 'markAllRead']);
});
