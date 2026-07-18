<?php

namespace App\Services;

use App\DTOs\GeoCoordinate;
use App\Exceptions\LocationOutsideServiceAreaException;
use App\Models\Route;
use App\Models\ServiceArea;
use Illuminate\Support\Facades\DB;

/**
 * Geofencing — trách nhiệm DUY NHẤT: xác định một tọa độ có được phép dùng
 * làm điểm đón/điểm trả của một tuyến hay không.
 *
 * Nguyên tắc:
 *  - Chỉ nhận GeoCoordinate (đã validate biên độ + chống đảo trục ngay khi tạo);
 *    không nhận cặp float rời để tránh đảo tham số.
 *  - Vùng phục vụ lấy từ CẤU HÌNH TUYẾN (routes.pickup/dropoff_service_area_id),
 *    không bao giờ nhận area id / province_code / cờ is_valid từ frontend.
 *  - Geometry đi vào MySQL duy nhất qua GeometryFactory (SRID 4326, axis-order
 *    chuẩn hóa một chỗ); so khớp polygon bằng ST_Intersects (exact, không dừng ở MBR).
 *  - Không gọi Google Maps/Mapbox — reverse geocoding chỉ để hiển thị địa chỉ.
 *  - KHÔNG chứa logic ghế, giá vé, thanh toán hay tạo booking.
 */
class ServiceAreaService
{
    public function __construct(
        private readonly GeometryFactory $geometryFactory,
    ) {}

    /**
     * Kiểm tra điểm đón & điểm trả của một booking theo cấu hình tuyến.
     * Tuyến chưa cấu hình vùng (null) → bỏ qua kiểm tra polygon phía đó.
     *
     * @throws LocationOutsideServiceAreaException điểm nằm ngoài vùng phục vụ (HTTP 422)
     */
    public function validateBookingLocations(Route $route, GeoCoordinate $pickup, GeoCoordinate $dropoff): void
    {
        // SQLite (test in-memory) không có hàm spatial → chỉ kiểm tra polygon trên MySQL.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $route->loadMissing(['pickupServiceArea', 'dropoffServiceArea']);

        if ($route->pickupServiceArea
            && ! $this->isPointInsideArea($route->pickupServiceArea, $pickup)) {
            throw new LocationOutsideServiceAreaException(
                "Điểm đón nằm ngoài vùng phục vụ ({$route->pickupServiceArea->name}) của tuyến"
            );
        }

        if ($route->dropoffServiceArea
            && ! $this->isPointInsideArea($route->dropoffServiceArea, $dropoff)) {
            throw new LocationOutsideServiceAreaException(
                "Điểm trả nằm ngoài vùng phục vụ ({$route->dropoffServiceArea->name}) của tuyến"
            );
        }
    }

    /**
     * Điểm có nằm trong ranh giới của vùng không — ST_Intersects nên điểm nằm
     * ĐÚNG TRÊN biên vẫn tính là hợp lệ. Chỉ chạy trên MySQL.
     */
    public function isPointInsideArea(ServiceArea $area, GeoCoordinate $point): bool
    {
        $geom = $this->geometryFactory->point($point);

        $row = DB::selectOne(
            "select ST_Intersects(boundary, {$geom->sql}) as inside from service_areas where id = ?",
            [...$geom->bindings, $area->getKey()],
        );

        return (bool) ($row->inside ?? false);
    }
}
