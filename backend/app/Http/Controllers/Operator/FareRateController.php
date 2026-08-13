<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\SaveFareRatesRequest;
use App\Models\Operator;
use App\Services\FarePricingService;
use App\Services\VietnamAdministrative;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FareRateController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var Operator $operator */
        $operator = auth('operator')->user()->operator;

        return response()->json([
            'success' => true,
            'data' => [
                'rates' => $operator->fareRates()->orderByRaw('province_code IS NULL DESC')
                    ->orderBy('province_code')->orderBy('district_code')->get(),
                'rounding_step' => FarePricingService::ROUNDING_STEP,
                'min_price' => FarePricingService::MIN_PRICE,
                'max_price' => FarePricingService::MAX_PRICE,
            ],
        ]);
    }

    /** Lưu nguyên bảng giá: dòng không còn trong payload sẽ bị xoá. */
    public function save(SaveFareRatesRequest $request): JsonResponse
    {
        /** @var Operator $operator */
        $operator = auth('operator')->user()->operator;
        $rates = $request->validated()['rates'];

        DB::transaction(function () use ($operator, $rates) {
            $operator->fareRates()->delete();

            foreach ($rates as $rate) {
                $province = $rate['province_code'] ?? null;
                $district = $rate['district_code'] ?? null;

                $operator->fareRates()->create([
                    'province_code' => $province,
                    'district_code' => $district,
                    'province_name' => VietnamAdministrative::findProvince($province)['name'] ?? null,
                    'district_name' => VietnamAdministrative::findDistrict($province, $district)['name'] ?? null,
                    'base_fare' => $rate['base_fare'],
                    'price_per_km' => $rate['price_per_km'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu bảng giá vé',
            'data' => $operator->fareRates()->get(),
        ]);
    }
}
