<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DriverStatusEnum;
use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\LoginRequest;
use App\Http\Requests\Driver\RegisterDriverRequest;
use App\Models\Driver;
use App\Models\User;
use App\Services\PrivateDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function __construct(private readonly PrivateDocumentService $documents) {}

    public function register(RegisterDriverRequest $request): JsonResponse
    {
        $storedPaths = [];

        try {
            $storedPaths = [
                'id_card_front_path' => $this->documents->store($request->file('id_card_front'), 'drivers'),
                'id_card_back_path' => $this->documents->store($request->file('id_card_back'), 'drivers'),
                'license_front_path' => $this->documents->store($request->file('license_front'), 'drivers'),
            ];

            DB::transaction(function () use ($request, $storedPaths): void {
                $user = User::create([
                    'full_name' => $request->full_name,
                    'phone' => $request->phone,
                    'password' => $request->password,
                    'role' => UserRoleEnum::Driver,
                    'is_verified' => true,
                ]);

                Driver::create([
                    'user_id' => $user->id,
                    'operator_id' => $request->operator_id,
                    'license_number' => $request->license_number,
                    'license_class' => $request->license_class,
                    'license_expiry' => $request->license_expiry,
                    'id_card_number' => $request->id_card_number,
                    ...$storedPaths,
                    'status' => DriverStatusEnum::Pending,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công. Hồ sơ đang chờ admin xét duyệt.',
            ], 201);
        } catch (\Exception $e) {
            $this->documents->deleteMany($storedPaths);
            Log::error('Driver register failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
            ->where('role', UserRoleEnum::Driver)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Số điện thoại hoặc mật khẩu không đúng'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'Tài khoản đã bị khóa'], 403);
        }

        $driver = $user->driver;
        if (! $driver || $driver->status !== DriverStatusEnum::Verified) {
            return response()->json(['success' => false, 'message' => 'Tài khoản chưa được duyệt hoặc đã bị đình chỉ'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('driver_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'must_change_password' => (bool) $user->must_change_password,
                ],
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
        $user = $request->user();
        $driver = $user->driver->load('currentVehicle');
        $vehicle = $driver->currentVehicle;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'avatar_url' => $user->avatar_url,
                // Xe mặc định nhà xe gán (Option 3 hybrid) — null nếu chưa gán
                'vehicle' => $vehicle ? [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'color' => $vehicle->color,
                    'vehicle_type' => $vehicle->vehicle_type?->value,
                    'seat_count' => $vehicle->seat_count,
                ] : null,
                'driver' => [
                    'id' => $driver->id,
                    'rating_avg' => $driver->rating_avg,
                    'total_trips' => $driver->total_trips,
                    'is_online' => $driver->is_online,
                    'operator' => ['id' => $driver->operator->id, 'name' => $driver->operator->company_name],
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

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'unique:users,email,'.$user->id],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'avatar' => ['sometimes', 'image', 'max:2048'],
        ]);

        $data = $request->only(['full_name', 'email', 'birth_date']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = Storage::url($path);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'avatar_url' => $user->avatar_url,
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

        $user->update([
            'password' => Hash::make($request->input('new_password')),
            'must_change_password' => false,  // mật khẩu đã được đổi, bỏ cờ bắt buộc
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công',
            'must_change_password' => false,
        ]);
    }

    /**
     * Tài xế cập nhật lại giấy tờ (CMND/GPLX). Mỗi `type` ánh xạ tới một cột
     * URL trên bảng drivers.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $columns = [
            'id_card_front' => 'id_card_front_path',
            'id_card_back' => 'id_card_back_path',
            'license_front' => 'license_front_path',
        ];

        $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys($columns))],
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'type.in' => 'Loại giấy tờ không hợp lệ',
            'file.required' => 'Vui lòng chọn tệp',
            'file.image' => 'Tệp phải là ảnh (jpg, png)',
            'file.max' => 'Ảnh tối đa 10MB',
        ]);

        $driver = $request->user()->driver;
        $column = $columns[$request->input('type')];
        $oldPath = $driver->{$column};
        $path = $this->documents->store($request->file('file'), 'drivers');

        try {
            $driver->update([$column => $path]);
        } catch (\Throwable $e) {
            $this->documents->delete($path);
            throw $e;
        }

        $this->documents->delete($oldPath);

        return response()->json([
            'success' => true,
            'message' => 'Tải giấy tờ thành công',
            'data' => [
                'type' => $request->input('type'),
                'url' => $this->documents->temporaryUrl($path),
            ],
        ]);
    }
}
