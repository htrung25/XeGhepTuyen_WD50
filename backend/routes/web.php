<?php

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
