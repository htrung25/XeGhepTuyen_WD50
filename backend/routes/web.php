<?php

use App\Http\Controllers\Public\PrivateDocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Backend chỉ phục vụ REST API — 4 SPA (customer/driver/operator/admin)
| do frontend/ đảm nhiệm (Vercel). API routes đăng ký ở bootstrap/app.php
| (routes/api_*.php), health check framework ở /up.
*/

Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'message' => 'XeGhep API — health check: /api/public/health, docs: /api/docs',
]));

// File định danh không public trực tiếp: URL được mã hóa, ký số và hết hạn ngắn.
// Tách khỏi global API throttle 60/phút vì một trang duyệt có thể tải nhiều ảnh.
Route::get('/api/public/private-documents', [PrivateDocumentController::class, 'show'])
    ->middleware(['signed', 'throttle:300,1,private-document:'])
    ->name('private-documents.show');
