<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverReviewReasonRequest;
use App\Http\Resources\Admin\DriverResource;
use App\Models\Driver;
use App\Services\AuditLogService;
use App\Services\DriverService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $drivers = Driver::with('user', 'operator')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->operator_id, fn ($q) => $q->where('operator_id', $request->operator_id))
            ->when($request->search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('full_name', 'LIKE', "%{$request->search}%")))
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => DriverResource::collection($drivers->items()),
            'meta' => ['current_page' => $drivers->currentPage(), 'total' => $drivers->total()],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $driver = Driver::with('user', 'operator', 'currentVehicle')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new DriverResource($driver)]);
    }

    public function approve(string $id): JsonResponse
    {
        $driver = Driver::with('user', 'operator')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        // Duyệt + cấp mật khẩu đăng nhập mới + gửi SMS cho tài xế.
        // KHÔNG trả mật khẩu về cho admin — chỉ tài xế nhận qua SMS (bảo đảm quyền lợi tài xế).
        // Guard + update are locked inside the service transaction; a concurrent
        // approval that loses the race throws and is reported as 422 here.
        try {
            $result = $this->driverService->approveAndIssueCredentials($driver);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'approve_driver',
            model: $result->driver,
            description: "Đã duyệt tài xế thành công: {$result->driver->user->full_name} (SĐT: {$result->driver->user->phone})",
            oldValues: ['status' => $result->oldStatus->value],
            newValues: ['status' => $result->newStatus->value]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt tài xế và gửi mật khẩu đăng nhập cho tài xế qua SMS',
            'data' => ['phone' => $result->driver->user->phone],
        ]);
    }

    /**
     * Cấp lại mật khẩu đăng nhập cho tài xế (khi SMS không tới / tài xế quên).
     */
    public function resetPassword(string $id): JsonResponse
    {
        $driver = Driver::with('user', 'operator')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        // Only an active driver can log in, so resetting the password for a
        // pending/rejected/suspended driver (blocked by gating) is pointless and
        // would issue a password + SMS for nothing.
        if ($driver->status !== DriverStatusEnum::Verified) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể cấp lại mật khẩu cho tài xế đang hoạt động'], 422);
        }

        // Chỉ tài xế nhận mật khẩu mới qua SMS — admin không xem được.
        $this->driverService->resetPassword($driver);

        $this->auditLog->log(
            action: 'reset_driver_password',
            model: $driver,
            description: "Đã cấp lại mật khẩu cho tài xế: {$driver->user->full_name} (SĐT: {$driver->user->phone})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cấp lại mật khẩu và gửi SMS cho tài xế',
            'data' => ['phone' => $driver->user->phone],
        ]);
    }

    public function reject(DriverReviewReasonRequest $request, string $id): JsonResponse
    {
        // Eager-load user: the audit log reads $driver->user (FK user_id is NOT NULL).
        $driver = Driver::with('user')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        try {
            $result = $this->driverService->reject($driver, $request->validated('reason'));
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'reject_driver',
            model: $result->driver,
            description: "Đã từ chối hồ sơ tài xế: {$result->driver->user->full_name} (SĐT: {$result->driver->user->phone}). Lý do: {$request->reason}",
            oldValues: ['status' => $result->oldStatus->value],
            newValues: ['status' => $result->newStatus->value, 'reject_reason' => $request->reason]
        );

        return response()->json(['success' => true, 'message' => 'Đã từ chối hồ sơ tài xế']);
    }

    public function suspend(DriverReviewReasonRequest $request, string $id): JsonResponse
    {
        // Eager-load user: audit log + is_active update read $driver->user (FK user_id NOT NULL).
        $driver = Driver::with('user')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        try {
            $result = $this->driverService->suspend($driver, $request->validated('reason'));
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $this->auditLog->log(
            action: 'suspend_driver',
            model: $result->driver,
            description: "Đã tạm đình chỉ hoạt động tài xế: {$result->driver->user->full_name} (SĐT: {$result->driver->user->phone}). Lý do: {$request->validated('reason')}",
            oldValues: ['status' => $result->oldStatus->value, 'user_is_active' => true],
            newValues: [
                'status' => $result->newStatus->value,
                'user_is_active' => false,
                'suspend_reason' => $request->validated('reason'),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Đã tạm đình chỉ tài xế']);
    }
}
