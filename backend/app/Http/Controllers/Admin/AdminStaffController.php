<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminStaffRequest;
use App\Http\Resources\Admin\AdminStaffResource;
use App\Models\AdminRole;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminStaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', UserRole::Admin)->with('adminRole');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'banned') {
            $query->where('is_active', false);
        }

        $staff = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => AdminStaffResource::collection($staff->items()),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'total' => $staff->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $staff = User::where('role', UserRole::Admin)->with('adminRole')->find($id);
        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Nhân viên không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new AdminStaffResource($staff)]);
    }

    public function store(StoreAdminStaffRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tempPassword = $this->generateTempPassword();

        $staff = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $tempPassword, // cast 'hashed' tự băm
            'role' => UserRole::Admin,
            'admin_role_id' => $data['admin_role_id'],
            'is_verified' => true,
            'is_active' => true,
        ]);

        app(AuditLogService::class)->log(
            action: 'create_admin',
            model: $staff,
            description: "Tạo nhân viên admin: {$staff->full_name} ({$staff->email})",
            newValues: ['admin_role_id' => $staff->admin_role_id],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo nhân viên admin',
            'data' => [
                'staff' => new AdminStaffResource($staff->load('adminRole')),
                'temp_password' => $tempPassword, // hiển thị 1 lần cho người tạo
            ],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $staff = User::where('role', UserRole::Admin)->with('adminRole')->find($id);
        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Nhân viên không tồn tại'], 404);
        }

        $validated = $request->validate([
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'email', 'max:100', 'unique:users,email,'.$staff->id],
            'phone' => ['sometimes', 'string', 'max:15', 'unique:users,phone,'.$staff->id],
            'admin_role_id' => ['sometimes', 'uuid', 'exists:admin_roles,id'],
        ], [
            'email.unique' => 'Email đã được sử dụng',
            'phone.unique' => 'Số điện thoại đã được sử dụng',
            'admin_role_id.exists' => 'Vai trò không tồn tại',
        ]);

        // Chống tự khóa: gỡ vai trò super của Super Admin cuối cùng
        if (array_key_exists('admin_role_id', $validated)
            && $staff->adminRole?->is_super
            && $validated['admin_role_id'] !== $staff->admin_role_id) {
            $newRole = AdminRole::find($validated['admin_role_id']);
            if (! $newRole?->is_super && $this->activeSuperAdminCount() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phải còn ít nhất một Super Admin đang hoạt động',
                ], 422);
            }
        }

        $oldValues = ['admin_role_id' => $staff->admin_role_id];
        $staff->update($validated);

        app(AuditLogService::class)->log(
            action: 'update_admin',
            model: $staff,
            description: "Cập nhật nhân viên admin: {$staff->full_name}",
            oldValues: $oldValues,
            newValues: ['admin_role_id' => $staff->admin_role_id],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật nhân viên',
            'data' => new AdminStaffResource($staff->load('adminRole')),
        ]);
    }

    public function ban(Request $request, string $id): JsonResponse
    {
        $staff = User::where('role', UserRole::Admin)->with('adminRole')->find($id);
        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Nhân viên không tồn tại'], 404);
        }

        if ($staff->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Không thể tự khóa tài khoản của mình'], 422);
        }

        if ($staff->adminRole?->is_super && $this->activeSuperAdminCount() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Phải còn ít nhất một Super Admin đang hoạt động',
            ], 422);
        }

        $staff->update(['is_active' => false]);
        $staff->tokens()->delete();

        app(AuditLogService::class)->log(
            action: 'ban_admin',
            model: $staff,
            description: "Khóa nhân viên admin: {$staff->full_name}",
        );

        return response()->json(['success' => true, 'message' => 'Đã khóa nhân viên']);
    }

    public function unban(string $id): JsonResponse
    {
        $staff = User::where('role', UserRole::Admin)->find($id);
        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Nhân viên không tồn tại'], 404);
        }

        $staff->update(['is_active' => true]);

        app(AuditLogService::class)->log(
            action: 'unban_admin',
            model: $staff,
            description: "Mở khóa nhân viên admin: {$staff->full_name}",
        );

        return response()->json(['success' => true, 'message' => 'Đã mở khóa nhân viên']);
    }

    public function resetPassword(string $id): JsonResponse
    {
        $staff = User::where('role', UserRole::Admin)->find($id);
        if (! $staff) {
            return response()->json(['success' => false, 'message' => 'Nhân viên không tồn tại'], 404);
        }

        $tempPassword = $this->generateTempPassword();
        $staff->update(['password' => $tempPassword]);
        $staff->tokens()->delete();

        app(AuditLogService::class)->log(
            action: 'reset_admin_password',
            model: $staff,
            description: "Đặt lại mật khẩu nhân viên admin: {$staff->full_name}",
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt lại mật khẩu',
            'data' => ['temp_password' => $tempPassword],
        ]);
    }

    /** Số Super Admin đang hoạt động (để chống tự khóa toàn hệ thống). */
    private function activeSuperAdminCount(): int
    {
        return User::where('role', UserRole::Admin)
            ->where('is_active', true)
            ->whereHas('adminRole', fn ($q) => $q->where('is_super', true))
            ->count();
    }

    private function generateTempPassword(): string
    {
        return Str::upper(Str::random(2)).random_int(100000, 999999);
    }
}
