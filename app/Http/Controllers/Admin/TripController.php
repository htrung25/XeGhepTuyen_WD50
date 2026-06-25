<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\LiveTripResource;
use App\Http\Resources\Admin\TripResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TripService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TripController extends Controller
{
    public function __construct(
        private readonly TripRepositoryInterface $tripRepo,
        private readonly TripService $tripService,
    ) {}

    /**
     * Chạy thủ công command xử lý chuyến quá giờ (hủy/hoàn tất + tất toán vé mồ côi),
     * không cần chờ scheduler 10 phút. Hữu ích ở local/khi cron chưa chạy.
     */
    public function autoResolve(): JsonResponse
    {
        Artisan::call('trips:auto-resolve');

        app(AuditLogService::class)->log(
            action: 'auto_resolve_trips',
            description: "Đã kích hoạt thủ công lệnh trips:auto-resolve xử lý các chuyến đi quá giờ"
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã chạy xử lý chuyến quá giờ',
            'data' => ['output' => trim(Artisan::output())],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $trips = $this->tripRepo->findAll([
            'date' => $request->date,
            'status' => $request->status,
            'search' => $request->search,
            'date_from' => $request->from_date,
            'date_to' => $request->to_date,
        ]);

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips),
            'meta' => ['total' => $trips->count()],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        // Eager load bookings với pickup/dropoff stop để trang detail hiển thị danh sách hành khách
        $trip->load(['bookings.pickupStop', 'bookings.dropoffStop']);

        return response()->json(['success' => true, 'data' => new TripResource($trip)]);
    }

    public function monitor(): JsonResponse
    {
        $trips = $this->tripRepo->findInProgress();

        return response()->json([
            'success' => true,
            'data' => LiveTripResource::collection($trips),
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $trip = $this->tripRepo->findById($id);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        if (! in_array($trip->status->value, ['scheduled', 'boarding'])) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không thể hủy ở trạng thái hiện tại'], 422);
        }

        // Hủy chuyến + hoàn 100% + bồi thường cho toàn bộ hành khách
        $oldStatus = $trip->status->value;
        $this->tripService->cancelTrip($trip, $request->reason, true);

        app(AuditLogService::class)->log(
            action: 'cancel_trip',
            model: $trip,
            description: "Đã hủy chuyến đi: {$trip->id}. Lý do: {$request->reason}",
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => 'cancelled']
        );

        return response()->json(['success' => true, 'message' => 'Đã hủy chuyến + hoàn tiền cho hành khách']);
    }
}
