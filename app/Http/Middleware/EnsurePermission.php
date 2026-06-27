<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Kiểm tra admin hiện tại có quyền (AdminPermission) tương ứng.
     * Chạy SAU `role:admin` nên user chắc chắn là admin; super admin bỏ qua mọi quyền.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user()?->hasPermission($permission)) {
            abort(403, 'Bạn không có quyền thực hiện chức năng này');
        }

        return $next($request);
    }
}
