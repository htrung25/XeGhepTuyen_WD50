<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\Admin\AdminRoleResource;
use App\Models\AdminRole;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /** Danh mục quyền gom theo module (cho cây checkbox UI). */
    public function permissions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => AdminPermission::catalog(),
        ]);
    }

    public function index(): JsonResponse
    {
        $roles = AdminRole::withCount('users')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => AdminRoleResource::collection($roles),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $role = AdminRole::withCount('users')->find($id);
        if (! $role) {
            return response()->json(['success' => false, 'message' => 'Vai trò không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new AdminRoleResource($role)]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = AdminRole::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'permissions' => array_values(array_unique($data['permissions'] ?? [])),
            'is_super' => false,
            'is_system' => false,
        ]);

        app(AuditLogService::class)->log(
            action: 'create_role',
            model: $role,
            description: "Tạo vai trò phân quyền: {$role->name}",
            newValues: ['permissions' => $role->permissions],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo vai trò',
            'data' => new AdminRoleResource($role),
        ], 201);
    }

    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $role = AdminRole::find($id);
        if (! $role) {
            return response()->json(['success' => false, 'message' => 'Vai trò không tồn tại'], 404);
        }

        if ($role->is_system && $request->has('permissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thay đổi quyền của vai trò hệ thống',
            ], 422);
        }

        $data = $request->validated();
        $oldValues = ['name' => $role->name, 'permissions' => $role->permissions];

        $payload = [];
        if (isset($data['name'])) {
            $payload['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }
        if (isset($data['permissions']) && ! $role->is_system) {
            $payload['permissions'] = array_values(array_unique($data['permissions']));
        }

        $role->update($payload);

        app(AuditLogService::class)->log(
            action: 'update_role',
            model: $role,
            description: "Cập nhật vai trò: {$role->name}",
            oldValues: $oldValues,
            newValues: ['name' => $role->name, 'permissions' => $role->permissions],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật vai trò',
            'data' => new AdminRoleResource($role),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $role = AdminRole::withCount('users')->find($id);
        if (! $role) {
            return response()->json(['success' => false, 'message' => 'Vai trò không tồn tại'], 404);
        }

        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vai trò hệ thống',
            ], 422);
        }

        if ($role->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Vai trò đang được gán cho nhân viên. Hãy chuyển họ sang vai trò khác trước khi xóa.',
            ], 422);
        }

        app(AuditLogService::class)->log(
            action: 'delete_role',
            model: $role,
            description: "Xóa vai trò: {$role->name}",
            oldValues: ['permissions' => $role->permissions],
        );

        $role->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa vai trò']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 1;
        while (AdminRole::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
