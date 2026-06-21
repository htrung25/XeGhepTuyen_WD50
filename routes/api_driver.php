<?php

use App\Http\Controllers\Driver\AuthController;
use App\Http\Controllers\Driver\CheckinController;
use App\Http\Controllers\Driver\EarningController;
use App\Http\Controllers\Driver\LocationController;
use App\Http\Controllers\Driver\TripController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver API Routes  (prefix: /api/driver)
|--------------------------------------------------------------------------
*/

// Auth — unauthenticated
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware(['auth:sanctum', 'role:driver'])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::put('auth/status', [AuthController::class, 'updateStatus']);

    // Trips
    Route::get('trips', [TripController::class, 'index']);
    Route::get('trips/history', [TripController::class, 'history']);
    Route::get('trips/{id}', [TripController::class, 'show']);
    Route::get('trips/{id}/passengers', [TripController::class, 'passengers']);
    Route::post('trips/{id}/start', [TripController::class, 'start']);
    Route::post('trips/{id}/complete', [TripController::class, 'complete']);

    // Check-in
    Route::post('checkin', [CheckinController::class, 'checkin']);
    Route::post('checkin/absent', [CheckinController::class, 'absent']);

    // Location
    Route::post('location', [LocationController::class, 'update']);

    // Earnings (bảng kê chỉ-xem — nhà xe quyết toán trực tiếp cho tài xế)
    Route::get('earnings', [EarningController::class, 'index']);
    Route::get('earnings/transactions', [EarningController::class, 'transactions']);
});
