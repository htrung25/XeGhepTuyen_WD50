<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Chặn truy cập chéo portal: token Sanctum của mọi role đều qua được
     * `auth:sanctum` (cùng model User), nên phải kiểm tra cột `role` để bảo
     * đảm chỉ đúng actor mới vào được nhóm route của portal tương ứng.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user()?->role->value !== $role) {
            abort(403, 'Không có quyền truy cập');
        }

        return $next($request);
    }
}
