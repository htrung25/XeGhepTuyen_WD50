<?php

use App\Http\Middleware\HandleAppearance;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
