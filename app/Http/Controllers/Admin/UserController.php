<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->search,
            'role' => $request->role,
        ];

        if ($request->status === 'active') {
            $filters['is_active'] = true;
        } elseif ($request->status === 'banned') {
            $filters['is_active'] = false;
        }

        $users = $this->userRepo->paginate($filters);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::withTrashed()->with('wallet')->find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại', 'code' => 'USER_NOT_FOUND'], 404);
        }

        return response()->json(['success' => true, 'data' => new UserResource($user)]);
    }

    public function ban(BanUserRequest $request, string $id): JsonResponse
    {
        $user = $this->userRepo->findById($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại', 'code' => 'USER_NOT_FOUND'], 404);
        }

        // Trang này chỉ quản lý KHÁCH HÀNG. Nhà xe/tài xế đình chỉ qua chức năng riêng
        // (đồng bộ status); quản trị viên qua mục Nhân viên (có anti-lockout).
        if (! $user->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ khóa được tài khoản khách hàng. Nhà xe/tài xế dùng chức năng đình chỉ riêng; quản trị viên dùng mục Nhân viên.',
                'code' => 'USER_NOT_CUSTOMER',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'Tài khoản này đã bị khóa', 'code' => 'USER_ALREADY_BANNED'], 422);
        }

        $reason = $request->validated('reason');
        $this->userService->ban($user);

        app(AuditLogService::class)->log(
            action: 'ban_user',
            model: $user,
            description: "Đã khoá tài khoản người dùng: {$user->full_name} (SĐT: {$user->phone}). Lý do: {$reason}",
            newValues: ['is_active' => false, 'ban_reason' => $reason]
        );

        return response()->json(['success' => true, 'message' => 'Đã khoá tài khoản người dùng']);
    }

    public function unban(string $id): JsonResponse
    {
        $user = $this->userRepo->findById($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại', 'code' => 'USER_NOT_FOUND'], 404);
        }

        if (! $user->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ mở khóa được tài khoản khách hàng.',
                'code' => 'USER_NOT_CUSTOMER',
            ], 422);
        }

        if ($user->is_active) {
            return response()->json(['success' => false, 'message' => 'Tài khoản đang hoạt động', 'code' => 'USER_ALREADY_ACTIVE'], 422);
        }

        $this->userService->unban($user);

        app(AuditLogService::class)->log(
            action: 'unban_user',
            model: $user,
            description: "Đã mở khoá tài khoản người dùng: {$user->full_name} (SĐT: {$user->phone})",
            newValues: ['is_active' => true]
        );

        return response()->json(['success' => true, 'message' => 'Đã mở khoá tài khoản']);
    }
}
