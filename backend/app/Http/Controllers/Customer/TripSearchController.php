<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SeatStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\TripSearchResource;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Services\TrackingService;
use App\Services\TripService;
use App\Services\VietnamAdministrative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripSearchController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripRepositoryInterface $tripRepo,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'from_province_code' => ['required_without:from_city', 'nullable', 'string', 'max:10'],
            'from_district_code' => ['required_with:from_province_code', 'nullable', 'string', 'max:10'],
            'to_province_code' => ['required_without:to_city', 'nullable', 'string', 'max:10'],
            'to_district_code' => ['required_with:to_province_code', 'nullable', 'string', 'max:10'],
            'from_city' => ['required_without:from_province_code', 'nullable', 'string'],
            // Điểm đến phải KHÁC điểm đi — chặn input phi lý ngay tại nguồn sự thật
            // (server) thay vì để lọt xuống query rồi trả 200 rỗng, gây hiểu nhầm "hết vé".
            'to_city' => ['required_without:to_province_code', 'nullable', 'string', 'different:from_city'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:4'],
            'sort' => ['nullable', 'in:price_asc,price_desc,depart_asc'],
        ], [
            'to_city.different' => 'Điểm đến phải khác điểm đi.',
        ]);

        $filters = $request->only([
            'from_city', 'to_city', 'from_district', 'to_district',
            'from_province_code', 'from_district_code', 'to_province_code', 'to_district_code',
            'date', 'passengers', 'sort',
        ]);

        foreach ([['from', 'Điểm đi'], ['to', 'Điểm đến']] as [$prefix, $label]) {
            $province = VietnamAdministrative::findProvince($filters["{$prefix}_province_code"] ?? null);
            $district = VietnamAdministrative::findDistrict(
                $filters["{$prefix}_province_code"] ?? null,
                $filters["{$prefix}_district_code"] ?? null,
            );
            if (($filters["{$prefix}_province_code"] ?? null) && ! $province) {
                return response()->json(['message' => "{$label} tỉnh/thành không hợp lệ"], 422);
            }
            if (($filters["{$prefix}_district_code"] ?? null) && ! $district) {
                return response()->json(['message' => "{$label} quận/huyện không thuộc tỉnh đã chọn"], 422);
            }
            if ($province && $district) {
                $filters["{$prefix}_city"] = $province['name'];
                $filters["{$prefix}_district"] = $district['name'];
            }
        }

        if (($filters['from_city'] ?? null) === ($filters['to_city'] ?? null)
            && ($filters['from_district'] ?? null) === ($filters['to_district'] ?? null)) {
            return response()->json(['message' => 'Điểm đến phải khác điểm đi.'], 422);
        }

        try {
            $trips = $this->tripService->search($filters);

            return response()->json([
                'success' => true,
                'data' => TripSearchResource::collection(collect($trips)),
                'meta' => ['total' => count($trips)],
            ]);
        } catch (\Exception $e) {
            Log::error('Trip search failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi tìm kiếm'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TripSearchResource($trip),
        ]);
    }

    public function seats(string $id): JsonResponse
    {
        $trip = $this->tripRepo->findById($id);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Chuyến đi không tồn tại'], 404);
        }

        $customerId = auth('customer')->id();
        $seats = $trip->seatMaps->map(fn ($seat) => [
            'id' => $seat->id,
            'seat_code' => $seat->seat_code,
            'seat_type' => $seat->seat_type->value,
            'price' => $seat->price,
            'status' => ($seat->status === SeatStatusEnum::Locked && $seat->locked_by === $customerId && ! $seat->isLockExpired())
                ? 'available'
                : ($seat->isLockExpired() ? 'available' : $seat->status->value),
        ]);

        return response()->json(['success' => true, 'data' => $seats]);
    }

    public function track(string $trackingCode): JsonResponse
    {
        $trip = $this->tripRepo->findByTrackingCode($trackingCode);

        if (! $trip) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy chuyến xe'], 404);
        }

        $location = null;
        if ($trip->isActive()) {
            $location = app(TrackingService::class)->getLocation($trip->driver);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'status' => $trip->status->value,
                'depart_at' => $trip->depart_at->format('H:i d/m/Y'),
                'driver_name' => $trip->driver->user->full_name,
                'plate_number' => $trip->vehicle->plate_number,
                'location' => $location,
            ],
        ]);
    }
}
