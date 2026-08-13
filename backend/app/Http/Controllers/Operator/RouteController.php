<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StoreRouteRequest;
use App\Http\Requests\Operator\UpdateRouteRequest;
use App\Models\Operator;
use App\Models\Route;
use App\Services\FarePricingService;
use App\Services\VietnamAdministrative;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    public function __construct(private readonly FarePricingService $pricing) {}

    public function index(): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $routes = $operator->routes()
            ->with(['pickupServiceArea', 'dropoffServiceArea'])
            ->get();

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        try {
            /** @var Operator $operator */
            $operator = auth('operator')->user()->operator;
            $validated = $request->validated();

            // Giá vé KHÔNG do nhà xe nhập — tính từ bảng giá theo km của huyện
            // điểm đi. Chưa cấu hình bảng giá vẫn tạo được tuyến (giá = 0, coi
            // như "chưa có giá"); ràng buộc bắt buộc nằm ở bước lên lịch chạy
            // (TripService::create) và giá sẽ tự lấy khi lưu bảng giá.
            $basePrice = $this->pricing->priceFor(
                $operator,
                $validated['origin_province_code'],
                $validated['origin_district_code'],
                (int) $validated['distance_km'],
            );

            $route = $operator->routes()->create(
                $this->routeAttributes($validated) + ['base_price' => $basePrice ?? 0]
            );

            return response()->json([
                'success' => true,
                'message' => 'Tạo tuyến đường thành công',
                'data' => $route->load(['pickupServiceArea', 'dropoffServiceArea']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Route create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $route = $this->findOwnedRoute($id, ['pickupServiceArea', 'dropoffServiceArea']);

        if (! $route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        return response()->json(['success' => true, 'data' => $route]);
    }

    public function update(UpdateRouteRequest $request, string $id): JsonResponse
    {
        $route = $this->findOwnedRoute($id);

        if (! $route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        if (! $route->canBeDeleted()) {
            return response()->json(['success' => false, 'message' => 'Không thể cập nhật tuyến đang có chuyến lịch'], 422);
        }

        $validated = $request->validated();
        $attributes = $this->routeAttributes($validated);

        // Đổi km hoặc đổi điểm đi ⇒ giá vé cũ không còn đúng với bảng giá.
        $originChanged = isset($validated['origin_province_code']);
        $distanceChanged = isset($validated['distance_km']);

        if ($originChanged || $distanceChanged) {
            /** @var Operator $operator */
            $operator = auth('operator')->user()->operator;

            $basePrice = $this->pricing->priceFor(
                $operator,
                $validated['origin_province_code'] ?? VietnamAdministrative::provinceCodeOfName($route->origin_city),
                $validated['origin_district_code'] ?? VietnamAdministrative::districtCodeOfName($route->origin_city, $route->origin_district),
                (int) ($validated['distance_km'] ?? $route->distance_km),
            );

            // Chưa có bảng giá cho khu vực mới ⇒ giá về 0 ("chưa cấu hình"),
            // chặn ở bước lên lịch chạy chứ không chặn ở đây.
            $attributes['base_price'] = $basePrice ?? 0;
        }

        $route->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $route->load(['pickupServiceArea', 'dropoffServiceArea']),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $route = $this->findOwnedRoute($id);

        if (! $route) {
            return response()->json(['success' => false, 'message' => 'Tuyến đường không tồn tại'], 404);
        }

        if (! $route->canBeDeleted()) {
            return response()->json(['success' => false, 'message' => 'Không thể xoá tuyến đang có chuyến lịch'], 422);
        }

        // Điểm dừng không còn được tạo/sửa qua UI, nhưng tuyến cũ vẫn còn bản
        // ghi — xoá kèm để không để lại FK mồ côi.
        DB::transaction(function () use ($route) {
            $route->stops()->delete();
            $route->delete();
        });

        return response()->json(['success' => true, 'message' => 'Đã xoá tuyến đường']);
    }

    /**
     * Đổi mã tỉnh/huyện của FE thành tên lưu trong DB. origin_city/dest_city
     * CHỈ chứa tên tỉnh (CityCodeResolver, TripService và TripRepository khớp
     * chuỗi chính xác trên cột này), huyện nằm ở cột riêng.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function routeAttributes(array $validated): array
    {
        $attributes = collect($validated)
            ->only(['name', 'distance_km', 'est_duration_min', 'is_round_trip', 'is_active'])
            ->all();

        if (isset($validated['origin_province_code'])) {
            $attributes['origin_city'] = VietnamAdministrative::findProvince($validated['origin_province_code'])['name'];
            $attributes['origin_district'] = VietnamAdministrative::findDistrict(
                $validated['origin_province_code'],
                $validated['origin_district_code'] ?? null,
            )['name'] ?? null;
        }

        if (isset($validated['dest_province_code'])) {
            $attributes['dest_city'] = VietnamAdministrative::findProvince($validated['dest_province_code'])['name'];
            $attributes['dest_district'] = VietnamAdministrative::findDistrict(
                $validated['dest_province_code'],
                $validated['dest_district_code'] ?? null,
            )['name'] ?? null;
        }

        return $attributes;
    }

    private function findOwnedRoute(string $id, array $with = []): ?Route
    {
        return auth('operator')->user()->operator
            ->routes()
            ->with($with)
            ->whereKey($id)
            ->first();
    }
}
