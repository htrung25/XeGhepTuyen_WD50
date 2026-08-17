<?php

use App\Http\Controllers\Customer\TripSearchController;
use App\Http\Controllers\Public\PartnerApplicationController;
use App\Http\Controllers\Public\ProvinceController;
use App\Http\Controllers\Public\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes  (prefix: /api/public)
| No authentication required — browsing trips for anonymous users
|--------------------------------------------------------------------------
*/

// Health check — frontend/monitoring xác nhận API sống (ngoài /up của framework)
Route::get('health', fn () => response()->json([
    'success' => true,
    'message' => 'API is running',
]));

// Danh mục hành chính (63 tỉnh + huyện) cho dropdown chọn điểm đi/đến
Route::get('provinces', [ProvinceController::class, 'index']);
Route::get('vouchers', [VoucherController::class, 'index']);

Route::get('trips', [TripSearchController::class, 'search']);
Route::get('trips/{id}', [TripSearchController::class, 'show']);
Route::get('trips/{id}/seats', [TripSearchController::class, 'seats']);

// Đăng ký trở thành đối tác nhà xe (lead — không cần đăng nhập)
Route::post('partner-applications', [PartnerApplicationController::class, 'store']);
