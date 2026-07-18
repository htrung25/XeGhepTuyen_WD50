<?php

namespace App\DTOs;

/**
 * Tọa độ WGS 84 — nguồn sự thật duy nhất về THỨ TỰ TRỤC trong ứng dụng.
 *
 * Quy ước SRID 4326:
 *  - Bên ngoài ứng dụng (API request/response, cột DB): latitude/longitude có TÊN rõ ràng.
 *  - GeoJSON/WKT input (Mapbox): thứ tự (longitude, latitude) → dùng fromLngLat().
 *  - MySQL constructor: luôn 'axis-order=long-lat' — chỉ GeometryFactory được viết.
 *
 * Validate ngay khi khởi tạo. Kinh độ Việt Nam ~102–110: nếu bị đảo vào vĩ độ
 * sẽ vượt 90 → lỗi đảo trục bị bắt tức thì thay vì lưu tọa độ sai.
 */
final readonly class GeoCoordinate
{
    private function __construct(
        public float $lat,
        public float $lng,
    ) {
        if ($lat < -90 || $lat > 90) {
            throw new \InvalidArgumentException(
                "Vĩ độ không hợp lệ ({$lat}): phải trong khoảng -90 đến 90 — giá trị 102–110 thường là kinh độ bị đảo trục"
            );
        }

        if ($lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException(
                "Kinh độ không hợp lệ ({$lng}): phải trong khoảng -180 đến 180"
            );
        }
    }

    /** Từ cặp (lat, lng) — thứ tự đặt tên bên ngoài ứng dụng (API request, cột DB). */
    public static function fromLatLng(float $lat, float $lng): self
    {
        return new self($lat, $lng);
    }

    /** Từ cặp (lng, lat) — thứ tự GeoJSON/WKT (Mapbox trả về dạng này). */
    public static function fromLngLat(float $lng, float $lat): self
    {
        return new self($lat, $lng);
    }

    /** WKT 'POINT(lng lat)' — chỉ dành cho GeometryFactory build geometry. */
    public function toWkt(): string
    {
        return sprintf('POINT(%.8F %.8F)', $this->lng, $this->lat);
    }
}
