<?php

namespace App\Services;

/**
 * Danh mục hành chính VN (63 tỉnh / 696 huyện, bản trước sáp nhập 2025) —
 * NGUỒN SỰ THẬT DUY NHẤT cho cả validation ở BE lẫn dropdown ở FE (FE lấy
 * qua GET /api/public/provinces), nên hai bên không bao giờ lệch nhau.
 *
 * Tên tỉnh đã cắt tiền tố "Thành phố"/"Tỉnh" khi build dataset để khớp
 * CityCodeResolver + dữ liệu routes cũ ("Hà Nội", "Hải Phòng").
 */
final class VietnamAdministrative
{
    /** @var array<int, array{code: string, name: string, districts: array<int, array{code: string, name: string}>}>|null */
    private static ?array $cache = null;

    /** @return array<int, array{code: string, name: string, districts: array<int, array{code: string, name: string}>}> */
    public static function provinces(): array
    {
        if (self::$cache === null) {
            $path = resource_path('data/vn-provinces.json');
            $raw = is_file($path) ? file_get_contents($path) : '[]';
            self::$cache = json_decode($raw ?: '[]', true) ?: [];
        }

        return self::$cache;
    }

    /** @return array{code: string, name: string, districts: array}|null */
    public static function findProvince(?string $code): ?array
    {
        if ($code === null || $code === '') {
            return null;
        }

        foreach (self::provinces() as $province) {
            if ($province['code'] === $code) {
                return $province;
            }
        }

        return null;
    }

    /** @return array{code: string, name: string}|null */
    public static function findDistrict(?string $provinceCode, ?string $districtCode): ?array
    {
        $province = self::findProvince($provinceCode);

        if (! $province || $districtCode === null || $districtCode === '') {
            return null;
        }

        foreach ($province['districts'] as $district) {
            if ($district['code'] === $districtCode) {
                return $district;
            }
        }

        return null;
    }

    public static function provinceExists(?string $code): bool
    {
        return self::findProvince($code) !== null;
    }

    public static function districtExists(?string $provinceCode, ?string $districtCode): bool
    {
        return self::findDistrict($provinceCode, $districtCode) !== null;
    }

    /** Chỉ dùng cho test — xoá cache tĩnh giữa các lần chạy */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
