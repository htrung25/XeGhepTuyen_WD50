<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\DriverResource;
use App\Models\Driver;
use App\Services\AuditLogService;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function __construct(private readonly DriverService $driverService) {}

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

        if ($driver->status !== DriverStatus::Pending) {
            return response()->json(['success' => false, 'message' => 'Tài xế này không ở trạng thái chờ duyệt'], 422);
        }

        // Duyệt + cấp mật khẩu đăng nhập mới + gửi SMS cho tài xế.
        // KHÔNG trả mật khẩu về cho admin — chỉ tài xế nhận qua SMS (bảo đảm quyền lợi tài xế).
        $this->driverService->approveAndIssueCredentials($driver);

        app(AuditLogService::class)->log(
            action: 'approve_driver',
            model: $driver,
            description: "Đã duyệt tài xế thành công: {$driver->user->full_name} (SĐT: {$driver->user->phone})",
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'verified']
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt tài xế và gửi mật khẩu đăng nhập cho tài xế qua SMS',
            'data' => ['phone' => $driver->user->phone],
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

        // Chỉ tài xế nhận mật khẩu mới qua SMS — admin không xem được.
        $this->driverService->resetPassword($driver);

        app(AuditLogService::class)->log(
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

    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        // Eager-load user: audit log đọc $driver->user (FK user_id NOT NULL — luôn có user).
        $driver = Driver::with('user')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        if ($driver->status !== DriverStatus::Pending) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể từ chối hồ sơ tài xế đang chờ duyệt'], 422);
        }

        $oldStatus = $driver->status->value;
        $driver->update(['status' => DriverStatus::Rejected, 'reject_reason' => $request->reason]);

        app(AuditLogService::class)->log(
            action: 'reject_driver',
            model: $driver,
            description: "Đã từ chối hồ sơ tài xế: {$driver->user->full_name} (SĐT: {$driver->user->phone}). Lý do: {$request->reason}",
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => DriverStatus::Rejected->value, 'reject_reason' => $request->reason]
        );

        return response()->json(['success' => true, 'message' => 'Đã từ chối hồ sơ tài xế']);
    }

    public function suspend(string $id): JsonResponse
    {
        // Eager-load user: audit log + update is_active đọc $driver->user (FK user_id NOT NULL).
        $driver = Driver::with('user')->find($id);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Tài xế không tồn tại'], 404);
        }

        if ($driver->status !== DriverStatus::Verified) {
            return response()->json(['success' => false, 'message' => 'Chỉ có thể đình chỉ tài xế đang hoạt động'], 422);
        }

        $oldStatus = $driver->status->value;
        // Bọc 2 update (drivers + users) trong 1 transaction: tránh lệch trạng thái
        // (driver bị đình chỉ nhưng user vẫn active) nếu lỗi giữa chừng.
        DB::transaction(function () use ($driver) {
            $driver->update(['status' => DriverStatus::Suspended]);
            $driver->user()->update(['is_active' => false]);
        });

        app(AuditLogService::class)->log(
            action: 'suspend_driver',
            model: $driver,
            description: "Đã tạm đình chỉ hoạt động tài xế: {$driver->user->full_name} (SĐT: {$driver->user->phone})",
            oldValues: ['status' => $oldStatus, 'user_is_active' => true],
            newValues: ['status' => DriverStatus::Suspended->value, 'user_is_active' => false]
        );

        return response()->json(['success' => true, 'message' => 'Đã tạm đình chỉ tài xế']);
    }
}
