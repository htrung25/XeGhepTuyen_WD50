<?php

use App\Http\Controllers\Operator\AuthController;
use App\Http\Controllers\Operator\BookingController;
use App\Http\Controllers\Operator\DriverController;
use App\Http\Controllers\Operator\OnboardingController;
use App\Http\Controllers\Operator\RevenueController;
use App\Http\Controllers\Operator\RouteController;
use App\Http\Controllers\Operator\TripController;
use App\Http\Controllers\Operator\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Operator API Routes  (prefix: /api/operator)
|--------------------------------------------------------------------------
*/

// Auth — unauthenticated
Route::post('auth/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me',    [AuthController::class, 'me']);
    Route::post('auth/logout',[AuthController::class, 'logout']);

    // Onboarding — tiến độ thêm xe so với cơ cấu đã khai lúc đăng ký
    Route::get('onboarding/fleet', [OnboardingController::class, 'fleet']);

    // Routes
    Route::get('routes',         [RouteController::class, 'index']);
    Route::post('routes',        [RouteController::class, 'store']);
    Route::get('routes/{id}',    [RouteController::class, 'show']);
    Route::put('routes/{id}',    [RouteController::class, 'update']);
    Route::delete('routes/{id}', [RouteController::class, 'destroy']);

    // Vehicles
    Route::get('vehicles',       [VehicleController::class, 'index']);
    Route::post('vehicles',      [VehicleController::class, 'store']);
    Route::get('vehicles/{id}',  [VehicleController::class, 'show']);
    Route::put('vehicles/{id}',  [VehicleController::class, 'update']);

    // Drivers
    Route::get('drivers',                    [DriverController::class, 'index']);
    Route::post('drivers',                   [DriverController::class, 'store']);
    Route::get('drivers/{id}',               [DriverController::class, 'show']);
    Route::put('drivers/{id}/vehicle',       [DriverController::class, 'assignVehicle']);
    Route::put('drivers/{id}/status',        [DriverController::class, 'updateStatus']);
    Route::post('drivers/{id}/reset-password', [DriverController::class, 'resetPassword']);

    // Trips
    Route::get('trips',                      [TripController::class, 'index']);
    Route::post('trips',                     [TripController::class, 'store']);
    Route::post('trips/bulk',                [TripController::class, 'bulkStore']);
    Route::get('trips/{id}',                 [TripController::class, 'show']);
    Route::post('trips/{id}/cancel',         [TripController::class, 'cancel']);
    Route::post('trips/{id}/complete',       [TripController::class, 'complete']);
    Route::get('trips/{id}/manifest',        [TripController::class, 'manifest']);
    Route::post('trips/{id}/manifest/export',[TripController::class, 'exportManifest']);

    // Bookings
    Route::get('bookings',       [BookingController::class, 'index']);
    Route::get('bookings/{id}',  [BookingController::class, 'show']);

    // Revenue
    Route::get('revenue/summary',        [RevenueController::class, 'summary']);
    Route::get('revenue/daily',          [RevenueController::class, 'daily']);
    Route::get('revenue/by-route',       [RevenueController::class, 'byRoute']);
    Route::get('revenue/by-driver',      [RevenueController::class, 'byDriver']);
    Route::get('revenue/payouts',        [RevenueController::class, 'payouts']);
    Route::post('revenue/payout-request',[RevenueController::class, 'requestPayout']);
});
