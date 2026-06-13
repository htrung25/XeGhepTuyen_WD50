<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\LoginRequest;
use App\Http\Requests\Driver\RegisterDriverRequest;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterDriverRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'full_name'   => $request->full_name,
                'phone'       => $request->phone,
                'password'    => $request->password,
                'role'        => UserRole::Driver,
                'is_verified' => true,
            ]);

            $idFront   = $request->file('id_card_front')->store('documents', 'public');
            $idBack    = $request->file('id_card_back')->store('documents', 'public');
            $licFront  = $request->file('license_front')->store('documents', 'public');

            Driver::create([
                'user_id'           => $user->id,
                'operator_id'       => $request->operator_id,
                'license_number'    => $request->license_number,
                'license_class'     => $request->license_class,
                'license_expiry'    => $request->license_expiry,
                'id_card_number'    => $request->id_card_number,
                'id_card_front_url' => \Illuminate\Support\Facades\Storage::url($idFront),
                'id_card_back_url'  => \Illuminate\Support\Facades\Storage::url($idBack),
                'license_front_url' => \Illuminate\Support\Facades\Storage::url($licFront),
                'status'            => DriverStatus::Pending,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công. Hồ sơ đang chờ admin xét duyệt.',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Driver register failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
                    ->where('role', UserRole::Driver)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Số điện thoại hoặc mật khẩu không đúng'], 401);
        }

        $driver = $user->driver;
        if (!$driver || $driver->status !== DriverStatus::Verified) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa được duyệt hoặc đã bị đình chỉ'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('driver_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data'    => [
                'token'  => $token,
                'user'   => ['id' => $user->id, 'full_name' => $user->full_name, 'phone' => $user->phone, 'avatar_url' => $user->avatar_url],
                'driver' => ['id' => $driver->id, 'rating_avg' => $driver->rating_avg, 'status' => $driver->status->value],
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
        $user    = $request->user();
        $driver  = $user->driver->load('currentVehicle');
        $vehicle = $driver->currentVehicle;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $user->id,
                'full_name'  => $user->full_name,
                'phone'      => $user->phone,
                'avatar_url' => $user->avatar_url,
                // Xe mặc định nhà xe gán (Option 3 hybrid) — null nếu chưa gán
                'vehicle'    => $vehicle ? [
                    'id'           => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'brand'        => $vehicle->brand,
                    'model'        => $vehicle->model,
                    'year'         => $vehicle->year,
                    'color'        => $vehicle->color,
                    'vehicle_type' => $vehicle->vehicle_type?->value,
                    'seat_count'   => $vehicle->seat_count,
                ] : null,
                'driver'     => [
                    'id'          => $driver->id,
                    'rating_avg'  => $driver->rating_avg,
                    'total_trips' => $driver->total_trips,
                    'is_online'   => $driver->is_online,
                    'operator'    => ['id' => $driver->operator->id, 'name' => $driver->operator->company_name],
                ],
            ],
        ]);
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate(['is_online' => ['required', 'boolean']]);

        $request->user()->driver->update(['is_online' => $request->is_online]);

        return response()->json([
            'success' => true,
            'message' => $request->is_online ? 'Đã bật trạng thái sẵn sàng' : 'Đã tắt trạng thái sẵn sàng',
        ]);
    }
}
