<?php

namespace App\Http\Controllers\Customer;

use App\Enums\UserRole;
use App\Exceptions\InvalidOtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\LoginRequest;
use App\Http\Requests\Customer\RegisterRequest;
use App\Http\Requests\Customer\SendOtpRequest;
use App\Jobs\SendSmsNotificationJob;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Dedoc\Scramble\Attributes\BodyParameter;

class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    #[OA\Post(
        path: "/api/customer/auth/send-otp",
        summary: "Gửi OTP xác thực số điện thoại",
        tags: ["Customer Auth"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["phone"],
            properties: [
                new OA\Property(property: "phone", type: "string", example: "0900000000", description: "Số điện thoại Việt Nam")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Gửi OTP thành công",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Mã OTP đã được gửi đến số điện thoại của bạn")
            ]
        )
    )]
    #[OA\Response(
        response: 429,
        description: "Quá nhiều yêu cầu OTP",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Vui lòng đợi trước khi gửi lại OTP")
            ]
        )
    )]
    #[BodyParameter('phone', 'Số điện thoại Việt Nam nhận mã OTP.', required: true, type: 'string', example: '0900000000')]
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $otp = $this->otpService->send($request->phone);

            SendSmsNotificationJob::dispatch(
                $request->phone,
                "[XeGhep] Mã OTP của bạn là: {$otp}. Có hiệu lực trong 5 phút. Không chia sẻ mã này."
            )->onQueue('notifications');

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến số điện thoại của bạn',
            ]);
        } catch (InvalidOtpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 429);
        } catch (\Exception $e) {
            Log::error('SendOtp failed', ['phone' => $request->phone, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    #[BodyParameter('phone', 'Số điện thoại đã nhận mã OTP.', required: true, type: 'string', example: '0900000000')]
    #[BodyParameter('otp', 'Mã OTP gồm 6 chữ số.', required: true, type: 'string', example: '123456')]
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'otp'   => ['required', 'digits:6'],
        ]);

        try {
            $this->otpService->verify($request->phone, $request->otp);
            return response()->json(['success' => true, 'message' => 'Xác thực OTP thành công']);
        } catch (InvalidOtpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    #[BodyParameter('phone', 'Số điện thoại đăng ký.', required: true, type: 'string', example: '0912345678')]
    #[BodyParameter('full_name', 'Họ và tên khách hàng.', required: true, type: 'string', example: 'Nguyễn Văn A')]
    #[BodyParameter('email', 'Địa chỉ email liên hệ (Không bắt buộc).', required: false, type: 'string', example: 'nguyenvana@gmail.com')]
    #[BodyParameter('password', 'Mật khẩu đăng nhập tối thiểu 6 ký tự.', required: true, type: 'string', example: '123456')]
    #[BodyParameter('password_confirmation', 'Nhập lại mật khẩu để xác nhận.', required: true, type: 'string', example: '123456')]
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'full_name'   => $request->full_name,
                'phone'       => $request->phone,
                'email'       => $request->email,
                'password'    => $request->password,
                'role'        => UserRole::Customer,
                'is_verified' => true,
            ]);

            $token = $user->createToken('customer_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công',
                'data'    => ['token' => $token, 'user' => $this->userResponse($user)],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Register failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại'], 500);
        }
    }

    #[OA\Post(
        path: "/api/customer/auth/login",
        summary: "Đăng nhập tài khoản khách hàng",
        tags: ["Customer Auth"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["phone", "password"],
            properties: [
                new OA\Property(property: "phone", type: "string", example: "0900000000"),
                new OA\Property(property: "password", type: "string", example: "123456")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Đăng nhập thành công",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "message", type: "string", example: "Đăng nhập thành công"),
                new OA\Property(property: "data", type: "object", properties: [
                    new OA\Property(property: "token", type: "string", example: "1|AbcDeFg..."),
                    new OA\Property(property: "user", type: "object")
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Sai số điện thoại hoặc mật khẩu",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: false),
                new OA\Property(property: "message", type: "string", example: "Số điện thoại hoặc mật khẩu không đúng")
            ]
        )
    )]
    #[BodyParameter('phone', 'Số điện thoại đăng nhập của khách hàng.', required: true, type: 'string', example: '0900000000')]
    #[BodyParameter('password', 'Mật khẩu đăng nhập.', required: true, type: 'string', example: '123456')]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)
                    ->where('role', UserRole::Customer)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Số điện thoại hoặc mật khẩu không đúng',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('customer_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data'    => ['token' => $token, 'user' => $this->userResponse($user)],
        ]);
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
            'data'    => $this->userResponse($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email'     => ['sometimes', 'email', 'unique:users,email,' . auth('customer')->id()],
            'avatar'    => ['sometimes', 'image', 'max:2048'],
        ]);

        $data = $request->only(['full_name', 'email']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        auth('customer')->user()->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'data'    => $this->userResponse(auth('customer')->user()),
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

        $user = auth('customer')->user();

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác',
                'code'    => 'INVALID_CURRENT_PASSWORD',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->input('new_password'))]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công',
        ]);
    }

    private function userResponse(User $user): array
    {
        return [
            'id'          => $user->id,
            'full_name'   => $user->full_name,
            'phone'       => $user->phone,
            'email'       => $user->email,
            'avatar_url'  => $user->avatar_url,
            'is_verified' => $user->is_verified,
        ];
    }
}
