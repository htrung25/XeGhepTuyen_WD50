<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $userRepo) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->search,
            'role'   => $request->role,
        ];

        if ($request->status === 'active') {
            $filters['is_active'] = true;
        } elseif ($request->status === 'banned') {
            $filters['is_active'] = false;
        }

        $users = $this->userRepo->paginate($filters);

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users->items()),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::withTrashed()->with('wallet')->find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new UserResource($user)]);
    }

    public function ban(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại'], 404);
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'Đã khoá tài khoản người dùng']);
    }

    public function unban(string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Người dùng không tồn tại'], 404);
        }

        $user->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => 'Đã mở khoá tài khoản']);
    }
}
