<?php

namespace App\Http\Controllers\Operator;

use App\Enums\TripStatus;
use App\Exceptions\TripNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StoreTripRequest;
use App\Http\Resources\Operator\TripResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TripController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripRepositoryInterface $tripRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $trips = $this->tripRepo->findByOperator($operator->id, [
            'date' => $request->date,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'status' => $request->status,
        ]);

        return response()->json(['success' => true, 'data' => TripResource::collection($trips)]);
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        try {
            $trip = $this->tripService->create(array_merge($request->validated(), ['operator_id' => $operator->id]));

            return response()->json([
                'success' => true,
                'message' => 'Tạo chuyến thành công',
                'data' => new TripResource($trip),
            ], 201);
        } catch (TripNotAvailableException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Trip create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'trips' => ['required', 'array', 'min:1', 'max:200'],
            'trips.*.route_id' => ['required', 'uuid', 'exists:routes,id'],
            'trips.*.vehicle_id' => ['required', 'uuid', 'exists:vehicles,id'],
            'trips.*.driver_id' => ['required', 'uuid', 'exists:drivers,id'],
            'trips.*.depart_at' => ['required', 'date', 'after:now'],
            'trips.*.base_price' => ['required', 'integer', 'min:1000'],
        ]);

        $operator = auth('operator')->user()->operator;

        try {
            ['created' => $created, 'skipped' => $skipped] = $this->tripService->bulkCreate($request->trips, $operator->id);

            $message = "Đã tạo {$created} chuyến";
            if ($skipped > 0) {
                $message .= " (bỏ qua {$skipped} chuyến trùng giờ hoặc không hợp lệ)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ], 201);
        } catch (TripNotAvailableException|\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Bulk trip create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);
        $operator = auth('operator')->user()->operator;

        if (! $trip || $trip->vehicle->operator_id !== $operator->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => new TripResource($trip)]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $trip = $this->tripRepo->findById($id);
        $operator = auth('operator')->user()->operator;

        if (! $trip || $trip->vehicle->operator_id !== $operator->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        try {
            $this->tripService->cancelTrip($trip, $request->reason, true);

            return response()->json(['success' => true, 'message' => 'Đã huỷ chuyến. Hoàn tiền 100% + bồi thường 20k cho hành khách']);
        } catch (\Exception $e) {
            Log::error('Operator cancel trip failed', ['error' => $e->getMessage(), 'trip' => $id]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    /**
     * Nhà xe xác nhận chuyến QUÁ GIỜ thực tế đã chạy xong (tránh tự hủy + hoàn tiền oan).
     */
    public function complete(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);
        $operator = auth('operator')->user()->operator;

        if (! $trip || $trip->vehicle->operator_id !== $operator->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        // Chỉ áp dụng cho chuyến chưa chạy/đang đón khách VÀ đã quá giờ khởi hành
        if (! in_array($trip->status, [TripStatus::Scheduled, TripStatus::Boarding], true)) {
            return response()->json(['success' => false, 'message' => 'Chuyến này không ở trạng thái có thể xác nhận hoàn tất'], 422);
        }
        if ($trip->depart_at->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Chỉ xác nhận hoàn tất cho chuyến đã quá giờ khởi hành'], 422);
        }

        try {
            $this->tripService->markRanCompleted($trip);

            return response()->json(['success' => true, 'message' => 'Đã xác nhận chuyến hoàn tất']);
        } catch (\Exception $e) {
            Log::error('Operator complete trip failed', ['error' => $e->getMessage(), 'trip' => $id]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function manifest(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);
        $operator = auth('operator')->user()->operator;

        if (! $trip || $trip->vehicle->operator_id !== $operator->id) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        // Danh sách khách của chuyến (vé còn hiệu lực), 1 dòng / hành khách
        $passengers = $trip->bookings()
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'completed', 'no_show'])
            ->with(['passengers.seatMap', 'pickupStop', 'dropoffStop'])
            ->get()
            ->flatMap(fn ($booking) => $booking->passengers->map(fn ($p) => [
                'booking_code' => $booking->booking_code,
                'contact_name' => $p->full_name ?? $booking->contact_name,
                'contact_phone' => $booking->contact_phone,
                'seat_code' => $p->seatMap?->seat_code,
                'pickup_stop' => $booking->pickupStop?->stop_name,
                'pickup_address' => $booking->pickup_address,
                'dropoff_stop' => $booking->dropoffStop?->stop_name,
                'status' => $booking->booking_status->value,
                'checked_in' => $booking->booking_status->value === 'checked_in',
                'no_show' => $booking->booking_status->value === 'no_show',
            ]))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'trip' => [
                    'route' => "{$trip->route->origin_city} → {$trip->route->dest_city}",
                    'depart_at' => $trip->depart_at->format('Y-m-d H:i:s'),
                    'driver_name' => $trip->driver?->user?->full_name,
                    'vehicle_plate' => $trip->vehicle->plate_number,
                ],
                'passengers' => $passengers,
            ],
        ]);
    }

    /**
     * Xuất danh sách hành khách ra CSV (mở được bằng Excel, có BOM UTF-8 cho tiếng Việt).
     */
    public function exportManifest(string $id): StreamedResponse
    {
        $trip = $this->tripRepo->findById($id);
        $operator = auth('operator')->user()->operator;

        if (! $trip || $trip->vehicle->operator_id !== $operator->id) {
            abort(404, 'Chuyến đi không tồn tại');
        }

        $rows = $trip->bookings()
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'completed', 'no_show'])
            ->with(['passengers.seatMap', 'pickupStop', 'dropoffStop'])
            ->get()
            ->flatMap(fn ($booking) => $booking->passengers->map(fn ($p) => [
                $booking->booking_code,
                $p->full_name ?? $booking->contact_name,
                $booking->contact_phone,
                $p->seatMap?->seat_code,
                $booking->pickupStop?->stop_name,
                $booking->dropoffStop?->stop_name,
                $booking->booking_status->label(),
            ]));

        $filename = "manifest-{$trip->tracking_code}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 để Excel đọc đúng tiếng Việt
            fputcsv($out, ['Mã vé', 'Hành khách', 'SĐT', 'Ghế', 'Điểm đón', 'Điểm trả', 'Trạng thái']);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
