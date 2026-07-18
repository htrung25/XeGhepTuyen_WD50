<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Map tên thành phố hiển thị → mã vùng chuẩn (HN/HP).
 *
 * GIẢI PHÁP CHUYỂN TIẾP: routes hiện lưu origin_city/dest_city dạng text tự do.
 * Không map nghiệp vụ bằng tên hiển thị — chuẩn hóa (bỏ dấu, thường hóa, bỏ tiền
 * tố TP/Thành phố) rồi tra bảng alias tĩnh. Nâng cấp dài hạn: thêm cột
 * origin_city_code/dest_city_code vào routes và bỏ resolver này.
 */
final class CityCodeResolver
{
    /** Alias đã chuẩn hóa (không dấu, chữ thường, không tiền tố) → mã vùng */
    private const ALIASES = [
        'ha noi' => 'HN',
        'hanoi' => 'HN',
        'hai phong' => 'HP',
        'haiphong' => 'HP',
    ];

    public static function resolve(string $city): ?string
    {
        $normalized = self::normalize($city);

        if ($normalized === '') {
            return null;
        }

        return self::ALIASES[$normalized] ?? null;
    }

    private static function normalize(string $city): string
    {
        $n = mb_strtolower(trim(Str::ascii($city)));           // 'TP. Hà Nội' → 'tp. ha noi'
        $n = preg_replace('/^(tp\.?|thanh pho)\s*/', '', $n);  // bỏ tiền tố TP/Thành phố

        return trim(preg_replace('/\s+/', ' ', $n));
    }
}
