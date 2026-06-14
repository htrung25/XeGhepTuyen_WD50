<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OperatorStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OperatorResource;
use App\Models\Operator;
use App\Services\OperatorAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function __construct(private readonly OperatorAccountService $accountService) {}

    public function index(Request $request): JsonResponse
    {
        $operators = Operator::with('user', 'partnerApplication')
            ->withCount('vehicles')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('company_name', 'LIKE', "%{$request->search}%"))
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => OperatorResource::collection($operators->items()),
            'meta' => ['current_page' => $operators->currentPage(), 'total' => $operators->total()],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $operator = Operator::with('user', 'vehicles', 'drivers')->find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new OperatorResource($operator)]);
    }

    public function approve(string $id): JsonResponse
    {
        $operator = Operator::find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        if ($operator->status !== OperatorStatus::Pending) {
            return response()->json(['success' => false, 'message' => 'Nhà xe này không ở trạng thái chờ duyệt'], 422);
        }

        $operator->update(['status' => OperatorStatus::Verified, 'verified_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Đã duyệt nhà xe thành công']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $operator = Operator::find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        $operator->update(['status' => OperatorStatus::Rejected, 'reject_reason' => $request->reason]);

        return response()->json(['success' => true, 'message' => 'Đã từ chối nhà xe']);
    }

    public function suspend(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $operator = Operator::find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        $operator->update(['status' => OperatorStatus::Suspended]);
        $operator->user()->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Đã tạm đình chỉ nhà xe']);
    }

    /**
     * Đặt lại mật khẩu nhà xe — sinh mật khẩu tạm mới, gửi SMS và trả về cho admin
     * để chuyển trực tiếp khi SMS không tới.
     */
    public function resetPassword(string $id): JsonResponse
    {
        $operator = Operator::with('user')->find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        if (! $operator->user) {
            return response()->json(['success' => false, 'message' => 'Nhà xe chưa có tài khoản đăng nhập'], 422);
        }

        $tempPassword = $this->accountService->resetPassword($operator);

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt lại mật khẩu và gửi SMS cho nhà xe',
            'data' => [
                'phone' => $operator->user->phone,
                'temp_password' => $tempPassword,
            ],
        ]);
    }
}
