<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)
                    ->where('role', UserRole::Admin)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data'    => [
                'token' => $token,
                'user'  => $this->adminPayload($user),
            ],
        ]);
    }

    /** Thông tin admin + vai trò/quyền để FE gate menu & nút. */
    private function adminPayload(User $user): array
    {
        $user->loadMissing('adminRole');

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role' => $user->role->value,
            'admin_role' => $user->adminRole ? [
                'id' => $user->adminRole->id,
                'name' => $user->adminRole->name,
                'slug' => $user->adminRole->slug,
            ] : null,
            'is_super' => $user->isSuperAdmin(),
            'permissions' => $user->permissionKeys(),
        ];
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Đã đăng xuất']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->adminPayload($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email'     => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone'     => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'avatar'    => ['sometimes', 'image', 'max:2048'],
        ]);

        $data = $request->only(['full_name', 'email', 'phone']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'data'    => $this->adminPayload($user),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'old_password.required' => 'Vui lòng nhập mật khẩu hiện tại',
            'new_password.required'  => 'Vui lòng nhập mật khẩu mới',
            'new_password.min'       => 'Mật khẩu mới tối thiểu 8 ký tự',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp',
        ]);

        $user = $request->user();

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->input('new_password'))]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }
}
