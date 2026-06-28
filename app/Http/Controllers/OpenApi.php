<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "XeGhépTuyến API (DATN_WD50)",
    version: "1.0.0",
    description: "Hệ thống API quản lý và đặt xe ghép tuyến XeGhépTuyến"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local Development API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Nhập token Sanctum của bạn"
)]
class OpenApi
{
    // Lớp rỗng dùng chứa attributes OpenAPI chung cho dự án.
}
