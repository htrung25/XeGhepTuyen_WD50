<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OperatorStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OperatorResource;
use App\Models\Operator;
use App\Services\OperatorAccountService;
use App\Services\AuditLogService;
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
            ->when($request->search, function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('company_name', 'LIKE', "%{$search}%")
                        ->orWhere('tax_code', 'LIKE', "%{$search}%")
                        ->orWhere('business_license', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('full_name', 'LIKE', "%{$search}%")
                              ->orWhere('phone', 'LIKE', "%{$search}%")
                              ->orWhere('email', 'LIKE', "%{$search}%");
                        });
                });
            })
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

        $oldStatus = $operator->status->value;
        $operator->update(['status' => OperatorStatus::Verified, 'verified_at' => now()]);

        app(AuditLogService::class)->log(
            action: 'approve_operator',
            model: $operator,
            description: "Đã duyệt nhà xe thành công: {$operator->company_name}",
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => OperatorStatus::Verified->value, 'verified_at' => now()->format('Y-m-d H:i:s')]
        );

        return response()->json(['success' => true, 'message' => 'Đã duyệt nhà xe thành công']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $operator = Operator::find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        $oldStatus = $operator->status->value;
        $operator->update(['status' => OperatorStatus::Rejected, 'reject_reason' => $request->reason]);

        app(AuditLogService::class)->log(
            action: 'reject_operator',
            model: $operator,
            description: "Đã từ chối nhà xe: {$operator->company_name}. Lý do: {$request->reason}",
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => OperatorStatus::Rejected->value, 'reject_reason' => $request->reason]
        );

        return response()->json(['success' => true, 'message' => 'Đã từ chối nhà xe']);
    }

    public function suspend(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $operator = Operator::find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        $oldStatus = $operator->status->value;
        $operator->update(['status' => OperatorStatus::Suspended]);
        $operator->user()->update(['is_active' => false]);

        app(AuditLogService::class)->log(
            action: 'suspend_operator',
            model: $operator,
            description: "Đã tạm đình chỉ nhà xe: {$operator->company_name}. Lý do: {$request->reason}",
            oldValues: ['status' => $oldStatus, 'user_is_active' => true],
            newValues: ['status' => OperatorStatus::Suspended->value, 'user_is_active' => false]
        );

        return response()->json(['success' => true, 'message' => 'Đã tạm đình chỉ nhà xe']);
    }

    public function restore(string $id): JsonResponse
    {
        $operator = Operator::find($id);

        if (! $operator) {
            return response()->json(['success' => false, 'message' => 'Nhà xe không tồn tại'], 404);
        }

        if ($operator->status !== OperatorStatus::Suspended) {
            return response()->json(['success' => false, 'message' => 'Nhà xe này không ở trạng thái đình chỉ'], 422);
        }

        $oldStatus = $operator->status->value;
        $operator->update(['status' => OperatorStatus::Verified]);
        if ($operator->user) {
            $operator->user->update(['is_active' => true]);
        }

        app(AuditLogService::class)->log(
            action: 'restore_operator',
            model: $operator,
            description: "Đã khôi phục hoạt động cho nhà xe: {$operator->company_name}",
            oldValues: ['status' => $oldStatus, 'user_is_active' => false],
            newValues: ['status' => OperatorStatus::Verified->value, 'user_is_active' => true]
        );

        return response()->json(['success' => true, 'message' => 'Đã khôi phục hoạt động cho nhà xe']);
    }

    /**
     * Đặt lại mật khẩu nhà xe — sinh mật khẩu tạm mới và gửi SMS cho nhà xe.
     * KHÔNG trả mật khẩu về cho admin (bảo đảm quyền lợi nhà xe).
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

        $this->accountService->resetPassword($operator);

        app(AuditLogService::class)->log(
            action: 'reset_operator_password',
            model: $operator,
            description: "Đã đặt lại mật khẩu đăng nhập cho nhà xe: {$operator->company_name} (SĐT: {$operator->user->phone})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã đặt lại mật khẩu và gửi SMS cho nhà xe',
            'data' => ['phone' => $operator->user->phone],
        ]);
    }
}
