<?php

use App\Http\Middleware\EnsurePermissionMiddleware;
use App\Http\Middleware\EnsureUserRoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['api', 'throttle:60,1'])
                ->prefix('api/public')
                ->group(base_path('routes/api_public.php'));

            Route::middleware(['api', 'throttle:60,1'])
                ->prefix('api/customer')
                ->group(base_path('routes/api_customer.php'));

            Route::middleware(['api', 'throttle:60,1'])
                ->prefix('api/driver')
                ->group(base_path('routes/api_driver.php'));

            Route::middleware(['api', 'throttle:60,1'])
                ->prefix('api/operator')
                ->group(base_path('routes/api_operator.php'));

            Route::middleware(['api', 'throttle:60,1'])
                ->prefix('api/admin')
                ->group(base_path('routes/api_admin.php'));
        },
    )
    // FE gọi POST /api/broadcasting/auth với Bearer token (xem useWebSocket.ts)
    // nên route auth private channel phải nằm dưới prefix api + auth:sanctum,
    // không dùng web/session mặc định.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum', 'throttle:60,1']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tin proxy (ngrok/reverse proxy) để nhận diện đúng scheme HTTPS từ X-Forwarded-*.
        $middleware->trustProxies(at: '*');

        // Backend chỉ phục vụ API: request chưa xác thực phải trả JSON 401,
        // không redirect sang route web "login" (route này không tồn tại).
        $middleware->redirectGuestsTo(null);

        // Chặn truy cập chéo portal sau khi auth:sanctum xác thực token.
        // `permission` kiểm tra quyền chi tiết của admin (RBAC).
        $middleware->alias([
            'role' => EnsureUserRoleMiddleware::class,
            'permission' => EnsurePermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
