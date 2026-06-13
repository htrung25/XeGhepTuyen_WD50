<?php

namespace App\Http\Controllers\Operator;

use App\Enums\OperatorStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\LoginRequest;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
                    ->where('role', UserRole::Operator)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Số điện thoại hoặc mật khẩu không đúng'], 401);
        }

        $operator = $user->operator;
        if (!$operator || $operator->status !== OperatorStatus::Verified) {
            return response()->json(['success' => false, 'message' => 'Tài khoản nhà xe chưa được kích hoạt'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('operator_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data'    => [
                'token'    => $token,
                'user'     => ['id' => $user->id, 'full_name' => $user->full_name, 'email' => $user->email],
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
        $user     = $request->user();
        $operator = $user->operator;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $user->id,
                'full_name'    => $user->full_name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'operator'     => [
                    'id'            => $operator->id,
                    'company_name'  => $operator->company_name,
                    'tax_number'    => $operator->tax_number,
                    'license_number'=> $operator->license_number,
                    'status'        => $operator->status->value,
                    'commission_rate'=> $operator->commission_rate,
                ],
            ],
        ]);
    }
}
