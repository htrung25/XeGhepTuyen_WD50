<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StoreRouteRequest;
use App\Http\Requests\Operator\UpdateRouteRequest;
use App\Models\Operator;
use App\Models\Route;
use App\Services\FarePricingService;
use App\Services\RouteUniquenessService;
use App\Services\VietnamAdministrative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    public function __construct(
        private readonly FarePricingService $pricing,
        private readonly RouteUniquenessService $uniqueness,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $operator = auth('operator')->user()->operator;

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive,all'],
        ]);

        $routes = $operator->routes()
            // Các màn hình nghiệp vụ mặc định chỉ dùng tuyến đang hoạt động.
            // Trang quản lý truyền status=all để có thể tìm và khôi phục tuyến tạm ngừng.
            ->when(($validated['status'] ?? 'active') === 'active', fn ($query) => $query->active())
            ->when(($validated['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                // LIKE dùng collation của DB (case-insensitive ở production);
                // giữ nguyên Unicode để không làm hỏng chữ Việt khi chạy SQLite.
                $term = '%'.trim($search).'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('origin_city', 'like', $term)
                        ->orWhere('origin_district', 'like', $term)
                        ->orWhere('dest_city', 'like', $term)
                        ->orWhere('dest_district', 'like', $term);
                });
            })
            ->with(['pickupServiceArea', 'dropoffServiceArea'])
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $routes]);
    }

    public function store(StoreRouteRequest $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = auth('operator')->user()->operator;
        $validated = $request->validated();
        $attributes = $this->routeAttributes($validated);

        // Ngoài try: ValidationException phải bay lên thành 422, không bị khối
        // catch bên dưới nuốt thành "Có lỗi xảy ra" 500.
        $this->uniqueness->assertUnique(
            $operator,
            $attributes['origin_city'] ?? null,
            $attributes['origin_district'] ?? null,
            $attributes['dest_city'] ?? null,
            $attributes['dest_district'] ?? null,
        );

        try {
            // Tuyến mới luôn ở trạng thái "chưa có giá" (base_price = 0): nhà xe
            // tạo tuyến trước, rồi vào Cấu hình giá vé gán đơn giá/km cho tuyến
            // đó. Chốt chặn nằm ở bước lên lịch chạy (TripService::create).
            $route = $operator->routes()->create($attributes + ['base_price' => 0]);

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

        // Chỉ những cột có trong payload mới đổi ⇒ so trùng theo giá trị SAU khi
        // gộp, và loại chính tuyến đang sửa ra khỏi phép so.
        $this->uniqueness->assertUnique(
            auth('operator')->user()->operator,
            $attributes['origin_city'] ?? $route->origin_city,
            $attributes['origin_district'] ?? $route->origin_district,
            $attributes['dest_city'] ?? $route->dest_city,
            $attributes['dest_district'] ?? $route->dest_district,
            $route->id,
        );

        $route->update($attributes);

        // Đổi số km ⇒ giá vé cũ không còn khớp đơn giá/km đã gán cho tuyến.
        // Tuyến chưa gán đơn giá vẫn giữ 0 ("chưa có giá").
        if (isset($validated['distance_km'])) {
            $route->update(['base_price' => $this->pricing->priceForRoute($route) ?? 0]);
        }

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
