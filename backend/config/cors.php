<?php

/*
|--------------------------------------------------------------------------
| CORS — frontend (Vercel) gọi API cross-origin
|--------------------------------------------------------------------------
| Auth dùng Sanctum Bearer token (không cookie) nên supports_credentials=false.
| Origin hợp lệ lấy từ env:
|   FRONTEND_URL          — origin production (vd https://xeghep.vercel.app)
|   FRONTEND_URL_PATTERN  — regex CÓ KIỂM SOÁT cho Vercel preview (tùy chọn,
|                           vd #^https://datn-wd50-[a-z0-9-]+\.vercel\.app$#)
| KHÔNG dùng '*' khi đã chạy production.
*/

return [

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL'),
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ])),

    'allowed_origins_patterns' => array_values(array_filter([
        env('FRONTEND_URL_PATTERN'),
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
