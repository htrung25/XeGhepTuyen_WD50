<?php

namespace App\Services;

use App\DTOs\GeoCoordinate;
use App\DTOs\GeometryExpression;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Nơi DUY NHẤT trong ứng dụng được viết ST_GeomFromText / 'axis-order=long-lat'.
 * Model, controller, service, repository không tự dựng WKT — mọi geometry đi vào
 * MySQL đều qua factory này, sau đó truy vấn geometry-với-geometry trực tiếp.
 *
 * Luồng chuẩn hóa: Mapbox/GeoJSON (lng, lat) → GeoCoordinate → GeometryFactory
 * → MySQL POINT/MULTIPOLYGON SRID 4326.
 */
class GeometryFactory
{
    public const SRID = 4326;

    private const GEOM_FROM_TEXT = "ST_GeomFromText(?, 4326, 'axis-order=long-lat')";

    /** Biểu thức POINT có binding — dùng trong whereRaw/selectOne. */
    public function point(GeoCoordinate $coord): GeometryExpression
    {
        return new GeometryExpression(self::GEOM_FROM_TEXT, [$coord->toWkt()]);
    }

    /**
     * Biểu thức geometry có binding từ WKT bất kỳ (MULTIPOLYGON ranh giới tỉnh…).
     * Tọa độ trong WKT theo thứ tự (lng lat) — đúng chuẩn GeoJSON.
     */
    public function fromWkt(string $wkt): GeometryExpression
    {
        return new GeometryExpression(self::GEOM_FROM_TEXT, [$wkt]);
    }

    /**
     * Bộ attribute tọa độ để GHI qua Eloquent (Booking::create…).
     *  - MySQL : ghi cột POINT (nguồn sự thật; lat/lng là generated column, MySQL
     *    cấm ghi trực tiếp giá trị vào generated column).
     *  - SQLite (test in-memory): không có kiểu spatial → ghi cặp lat/lng vật lý.
     */
    public function coordinateAttributes(string $prefix, GeoCoordinate $coord): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [
                "{$prefix}_lat" => $coord->lat,
                "{$prefix}_lng" => $coord->lng,
            ];
        }

        return ["{$prefix}_point" => $this->pointLiteral($coord)];
    }

    /**
     * POINT dạng literal — ngoại lệ DUY NHẤT không dùng binding, vì Eloquent không
     * bind được bên trong mảng attribute. An toàn theo cấu trúc: đầu vào chỉ là
     * 2 float đã validate trong GeoCoordinate, format %.8F không thể chứa SQL.
     */
    private function pointLiteral(GeoCoordinate $coord): Expression
    {
        return DB::raw(sprintf(
            "ST_GeomFromText('POINT(%.8F %.8F)', %d, 'axis-order=long-lat')",
            $coord->lng,
            $coord->lat,
            self::SRID,
        ));
    }
}
