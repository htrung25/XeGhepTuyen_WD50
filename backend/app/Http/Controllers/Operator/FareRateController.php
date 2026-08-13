<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\SaveFareRatesRequest;
use App\Models\Operator;
use App\Services\FarePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FareRateController extends Controller
{
    public function __construct(private readonly FarePricingService $pricing) {}

    /**
     * Danh sách TUYẾN của nhà xe kèm đơn giá đã gán — màn "Cấu hình giá vé"
     * chọn tuyến chứ không chọn tỉnh/huyện, và nhìn ngay tuyến nào chưa có giá.
     */
    public function index(): JsonResponse
    {
        /** @var Operator $operator */
        $operator = auth('operator')->user()->operator;

        $routes = $operator->routes()->with('fareRate')->orderBy('name')->get()
            ->map(fn ($route) => [
                'id' => $route->id,
                'name' => $route->name,
                'origin_city' => $route->origin_city,
                'origin_district' => $route->origin_district,
                'dest_city' => $route->dest_city,
                'dest_district' => $route->dest_district,
                'distance_km' => (int) $route->distance_km,
                'base_price' => (int) $route->base_price,
                'base_fare' => $route->fareRate ? (int) $route->fareRate->base_fare : null,
                'price_per_km' => $route->fareRate ? (float) $route->fareRate->price_per_km : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'routes' => $routes,
                'rounding_step' => FarePricingService::ROUNDING_STEP,
                'min_price' => FarePricingService::MIN_PRICE,
                'max_price' => FarePricingService::MAX_PRICE,
            ],
        ]);
    }

    /** Lưu nguyên bảng giá: tuyến không có trong payload quay về "chưa có giá". */
    public function save(SaveFareRatesRequest $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = auth('operator')->user()->operator;
        $rates = $request->validated()['rates'];

        DB::transaction(function () use ($operator, $rates) {
            $operator->fareRates()->delete();

            foreach ($rates as $rate) {
                $operator->fareRates()->create([
                    'route_id' => $rate['route_id'],
                    'base_fare' => $rate['base_fare'],
                    'price_per_km' => $rate['price_per_km'],
                ]);
            }

            $this->repriceRoutes($operator);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu bảng giá vé',
        ]);
    }

    /**
     * Đơn giá đổi ⇒ giá vé mọi tuyến của nhà xe được tính lại. Nhờ vậy nhà xe
     * tạo tuyến trước, gán giá sau — giá tự điền vào tuyến. Tuyến không được
     * gán giá giữ base_price = 0 và bị chặn khi lên lịch chạy.
     */
    private function repriceRoutes(Operator $operator): void
    {
        $operator->routes()->with('fareRate')->chunkById(200, function ($routes) {
            foreach ($routes as $route) {
                $price = $this->pricing->priceForRoute($route) ?? 0;

                if ((int) $route->base_price !== $price) {
                    $route->update(['base_price' => $price]);
                }
            }
        });
    }
}
