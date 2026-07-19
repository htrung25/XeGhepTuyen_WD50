<?php

namespace App\Services;

use App\DTOs\GeoCoordinate;
use App\Exceptions\LocationOutsideServiceAreaException;
use App\Exceptions\ServiceAreaNotConfiguredException;
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
     *
     * FAIL-CLOSED: tuyến thiếu vùng hoặc vùng đã tắt → chặn booking thay vì
     * bypass geofencing (chống lách qua tuyến chưa/không map được vùng).
     *
     * @throws ServiceAreaNotConfiguredException tuyến chưa cấu hình đủ vùng active (HTTP 422)
     * @throws LocationOutsideServiceAreaException điểm nằm ngoài vùng phục vụ (HTTP 422)
     */
    public function validateBookingLocations(Route $route, GeoCoordinate $pickup, GeoCoordinate $dropoff): void
    {
        $route->loadMissing(['pickupServiceArea', 'dropoffServiceArea']);

        // Kiểm tra fail-closed chạy trên MỌI driver (kể cả sqlite test).
        if (! $route->pickupServiceArea?->is_active || ! $route->dropoffServiceArea?->is_active) {
            throw new ServiceAreaNotConfiguredException;
        }

        // SQLite in-memory chỉ được phép bỏ qua spatial trong test. Mọi môi trường
        // vận hành phải fail-fast nếu cấu hình nhầm database không hỗ trợ contract này.
        if (DB::getDriverName() !== 'mysql') {
            if (app()->environment('testing')) {
                return;
            }

            throw new \RuntimeException('ServiceArea geofencing yêu cầu MySQL Spatial.');
        }

        if (! $this->isPointInsideArea($route->pickupServiceArea, $pickup)) {
            throw new LocationOutsideServiceAreaException(
                "Điểm đón nằm ngoài vùng phục vụ ({$route->pickupServiceArea->name}) của tuyến"
            );
        }

        if (! $this->isPointInsideArea($route->dropoffServiceArea, $dropoff)) {
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
