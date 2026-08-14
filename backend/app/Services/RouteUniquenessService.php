<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\Route;
use Illuminate\Validation\ValidationException;

/**
 * Chống trùng tuyến trong phạm vi MỘT nhà xe: cùng (huyện đi, tỉnh đi) →
 * (huyện đến, tỉnh đến) chỉ được có một tuyến.
 *
 * Bảng `routes` KHÔNG có unique index cho bộ này (chỉ có index
 * operator_id + is_active), và dữ liệu cũ có thể đã trùng nên không thể thêm
 * unique mà không dọn dữ liệu trước — vì vậy ràng buộc đặt ở tầng service.
 * Hệ quả: hai request gửi song song vẫn có thể lọt (không có khoá ở DB); nếu
 * cần chặn tuyệt đối thì phải dọn trùng rồi thêm unique index.
 */
final class RouteUniquenessService
{
    /**
     * @throws ValidationException khi nhà xe đã có tuyến cùng điểm đi/điểm đến
     */
    public function assertUnique(
        Operator $operator,
        ?string $originCity,
        ?string $originDistrict,
        ?string $destCity,
        ?string $destDistrict,
        ?string $exceptRouteId = null,
    ): void {
        $duplicate = $this->findDuplicate(
            $operator,
            $originCity,
            $originDistrict,
            $destCity,
            $destDistrict,
            $exceptRouteId,
        );

        if ($duplicate) {
            throw ValidationException::withMessages([
                'dest_district_code' => "Nhà xe đã có tuyến \"{$duplicate->name}\" đi từ ".
                    $this->place($originDistrict, $originCity).' đến '.
                    $this->place($destDistrict, $destCity).'.',
            ]);
        }
    }

    public function findDuplicate(
        Operator $operator,
        ?string $originCity,
        ?string $originDistrict,
        ?string $destCity,
        ?string $destDistrict,
        ?string $exceptRouteId = null,
    ): ?Route {
        // Tuyến chưa đủ thông tin điểm đi/đến (dữ liệu cũ) không đem so trùng.
        if (! $originCity || ! $destCity) {
            return null;
        }

        return $operator->routes()
            ->where('origin_city', $originCity)
            ->where('dest_city', $destCity)
            ->where(fn ($q) => $originDistrict === null
                ? $q->whereNull('origin_district')
                : $q->where('origin_district', $originDistrict))
            ->where(fn ($q) => $destDistrict === null
                ? $q->whereNull('dest_district')
                : $q->where('dest_district', $destDistrict))
            ->when($exceptRouteId, fn ($q) => $q->whereKeyNot($exceptRouteId))
            ->first();
    }

    private function place(?string $district, ?string $city): string
    {
        return $district ? "{$district}, {$city}" : (string) $city;
    }
}
