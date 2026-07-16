<?php

namespace App\Http\Controllers\Operator;

use App\Enums\OperatorStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
            ->where('role', UserRole::Operator)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Số điện thoại hoặc mật khẩu không đúng'], 401);
        }

        $operator = $user->operator;
        if (! $operator || $operator->status !== OperatorStatus::Verified) {
            return response()->json(['success' => false, 'message' => 'Tài khoản nhà xe chưa được kích hoạt'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('operator_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'user' => ['id' => $user->id, 'full_name' => $user->full_name, 'email' => $user->email],
                'operator' => ['id' => $operator->id, 'company_name' => $operator->company_name, 'status' => $operator->status->value],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Đã đăng xuất']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $operator = $user->operator;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'operator' => [
                    'id' => $operator->id,
                    'company_name' => $operator->company_name,
                    'tax_code' => $operator->tax_code,
                    'business_license' => $operator->business_license,
                    'bank_account' => $operator->bank_account,
                    'bank_name' => $operator->bank_name,
                    'bank_account_name' => $operator->bank_account_name,
                    'logo_url' => $operator->logo_url,
                    'description' => $operator->description,
                    'status' => $operator->status->value,
                    'commission_rate' => $operator->commission_rate,
                ],
            ],
        ]);
    }

    /**
     * Cập nhật hồ sơ nhà xe: thông tin liên hệ + tài khoản ngân hàng nhận tiền
     * (F-O01) + logo/mô tả. KHÔNG cho sửa tax_code/business_license tại đây —
     * đó là giấy tờ pháp lý do admin duyệt ([[project_partner_application]]).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $operator = $user->operator;

        $request->validate([
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'unique:users,email,'.$user->id],
            'company_name' => ['sometimes', 'string', 'max:200'],
            'bank_account' => ['sometimes', 'nullable', 'string', 'max:30'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'logo' => ['sometimes', 'image', 'max:2048'],
        ]);

        $user->fill($request->only(['full_name', 'email']));
        $user->save();

        $operatorData = $request->only([
            'company_name', 'bank_account', 'bank_name', 'bank_account_name', 'description',
        ]);

        if ($request->hasFile('logo')) {
            $operatorData['logo_url'] = Storage::url($request->file('logo')->store('operator-logos', 'public'));
        }

        $operator->fill($operatorData);
        $operator->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'data' => [
                'full_name' => $user->full_name,
                'email' => $user->email,
                'company_name' => $operator->company_name,
                'bank_account' => $operator->bank_account,
                'bank_name' => $operator->bank_name,
                'bank_account_name' => $operator->bank_account_name,
                'logo_url' => $operator->logo_url,
                'description' => $operator->description,
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'old_password.required' => 'Vui lòng nhập mật khẩu hiện tại',
            'new_password.required' => 'Vui lòng nhập mật khẩu mới',
            'new_password.min' => 'Mật khẩu mới tối thiểu 8 ký tự',
            'new_password.confirmed' => 'Xác nhận mật khẩu mới không khớp',
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác',
                'code' => 'INVALID_CURRENT_PASSWORD',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->input('new_password'))]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }
}
