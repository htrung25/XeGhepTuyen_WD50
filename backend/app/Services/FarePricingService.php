<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\OperatorFareRate;

/**
 * Tính giá vé tuyến theo km từ bảng giá của nhà xe. Nhà xe không nhập giá
 * tuyến bằng tay nữa — giá luôn là hàm của (bảng giá huyện điểm đi, số km).
 */
final class FarePricingService
{
    /** Bước làm tròn lên (đồng) */
    public const ROUNDING_STEP = 1000;

    /** Kẹp biên chặn dữ liệu vô lý (đồng) */
    public const MIN_PRICE = 10000;

    public const MAX_PRICE = 5000000;

    /**
     * Tra dòng giá áp dụng cho một điểm đi, ưu tiên từ hẹp đến rộng:
     * (tỉnh + huyện) → (tỉnh) → (mặc định nhà xe). Trả null nếu nhà xe
     * chưa cấu hình gì cả — nơi gọi phải báo lỗi thay vì tự đoán giá.
     */
    public function resolveRate(Operator $operator, ?string $provinceCode, ?string $districtCode): ?OperatorFareRate
    {
        $rates = $operator->fareRates()->get();

        $exact = $rates->first(fn (OperatorFareRate $r) => $r->province_code === $provinceCode
            && $r->district_code === $districtCode
            && $districtCode !== null);

        if ($exact) {
            return $exact;
        }

        $province = $rates->first(fn (OperatorFareRate $r) => $r->province_code === $provinceCode
            && $r->district_code === null
            && $provinceCode !== null);

        if ($province) {
            return $province;
        }

        return $rates->first(fn (OperatorFareRate $r) => $r->province_code === null && $r->district_code === null);
    }

    /** Giá vé = làm tròn lên bội số 1.000 của (phí mở cửa + đơn giá × km), kẹp biên. */
    public function calculate(OperatorFareRate $rate, int $distanceKm): int
    {
        $raw = (float) $rate->base_fare + ((float) $rate->price_per_km * $distanceKm);
        $rounded = (int) (ceil($raw / self::ROUNDING_STEP) * self::ROUNDING_STEP);

        return max(self::MIN_PRICE, min(self::MAX_PRICE, $rounded));
    }

    /**
     * Tiện ích cho controller: tra bảng giá rồi tính luôn.
     * Trả null khi nhà xe chưa cấu hình giá cho phạm vi nào phù hợp.
     */
    public function priceFor(Operator $operator, ?string $provinceCode, ?string $districtCode, int $distanceKm): ?int
    {
        $rate = $this->resolveRate($operator, $provinceCode, $districtCode);

        return $rate ? $this->calculate($rate, $distanceKm) : null;
    }
}
