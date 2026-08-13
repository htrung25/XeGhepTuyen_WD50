<?php

use App\Models\OperatorFareRate;
use App\Services\FarePricingService;

function rate(int $baseFare, float $perKm): OperatorFareRate
{
    return new OperatorFareRate(['base_fare' => $baseFare, 'price_per_km' => $perKm]);
}

it('làm tròn lên bội số 1.000', function () {
    $service = new FarePricingService;

    expect($service->calculate(rate(0, 1234), 10))->toBe(13000);   // 12.340 → 13.000
    expect($service->calculate(rate(5000, 1000), 100))->toBe(105000);
});

it('kẹp giá trong biên min max', function () {
    $service = new FarePricingService;

    expect($service->calculate(rate(0, 100), 1))->toBe(FarePricingService::MIN_PRICE);
    expect($service->calculate(rate(0, 100000), 2000))->toBe(FarePricingService::MAX_PRICE);
});
