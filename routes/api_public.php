<?php

use App\Http\Controllers\Customer\TripSearchController;
use App\Http\Controllers\Public\PartnerApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes  (prefix: /api/public)
| No authentication required — browsing trips for anonymous users
|--------------------------------------------------------------------------
*/

Route::get('trips',            [TripSearchController::class, 'search']);
Route::get('trips/{id}',       [TripSearchController::class, 'show']);
Route::get('trips/{id}/seats', [TripSearchController::class, 'seats']);

// Đăng ký trở thành đối tác nhà xe (lead — không cần đăng nhập)
Route::post('partner-applications', [PartnerApplicationController::class, 'store']);
