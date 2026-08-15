<?php

namespace App\Services;

use App\Models\OperatorFareRate;
use App\Models\Route;

/**
 * Tính giá vé của tuyến theo km từ đơn giá nhà xe gán cho CHÍNH tuyến đó.
 * Nhà xe không nhập giá vé tay: giá luôn là hàm của (đơn giá tuyến, số km).
 */
final class FarePricingService
{
    /** Bước làm tròn lên (đồng) */
    public const ROUNDING_STEP = 1000;

    /** Kẹp biên chặn dữ liệu vô lý (đồng) */
    public const MIN_PRICE = 10000;

    public const MAX_PRICE = 5000000;

    /** Giá vé = làm tròn lên bội số 1.000 của (phí mở cửa + đơn giá × km), kẹp biên. */
    public function calculate(OperatorFareRate $rate, int $distanceKm): int
    {
        $raw = (float) $rate->base_fare + ((float) $rate->price_per_km * $distanceKm);
        $rounded = (int) (ceil($raw / self::ROUNDING_STEP) * self::ROUNDING_STEP);

        return max(self::MIN_PRICE, min(self::MAX_PRICE, $rounded));
    }

    /**
     * Giá vé của một tuyến theo đơn giá đã gán. Trả null khi tuyến chưa được
     * gán giá — nơi gọi phải coi là "chưa có giá", không được tự đoán.
     */
    public function priceForRoute(Route $route): ?int
    {
        $rate = $route->fareRate()->first();

        return $rate ? $this->calculate($rate, (int) $route->distance_km) : null;
    }
}
