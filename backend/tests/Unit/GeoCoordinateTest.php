<?php

use App\DTOs\GeoCoordinate;

it('giữ đúng lat/lng khi tạo từ fromLatLng', function () {
    $coord = GeoCoordinate::fromLatLng(21.028511, 105.804817);

    expect($coord->lat)->toBe(21.028511)
        ->and($coord->lng)->toBe(105.804817);
});

it('đảo đúng thứ tự GeoJSON (lng, lat) qua fromLngLat', function () {
    $coord = GeoCoordinate::fromLngLat(105.804817, 21.028511);

    expect($coord->lat)->toBe(21.028511)
        ->and($coord->lng)->toBe(105.804817);
});

it('chống đảo trục: kinh độ Việt Nam (102–110) đưa nhầm vào vĩ độ bị chặn ngay', function () {
    // Nếu code nào đó truyền (lng, lat) vào fromLatLng, lng VN ~105 vượt 90 → nổ tức thì
    GeoCoordinate::fromLatLng(105.804817, 21.028511);
})->throws(InvalidArgumentException::class, 'Vĩ độ không hợp lệ');

it('chặn vĩ độ ngoài khoảng -90..90', function (float $lat) {
    GeoCoordinate::fromLatLng($lat, 105.8);
})->with([90.1, -90.1])->throws(InvalidArgumentException::class, 'Vĩ độ không hợp lệ');

it('chặn kinh độ ngoài khoảng -180..180', function (float $lng) {
    GeoCoordinate::fromLatLng(21.0, $lng);
})->with([180.1, -180.1])->throws(InvalidArgumentException::class, 'Kinh độ không hợp lệ');

it('chấp nhận giá trị biên ±90/±180', function () {
    expect(GeoCoordinate::fromLatLng(90.0, 180.0))->toBeInstanceOf(GeoCoordinate::class)
        ->and(GeoCoordinate::fromLatLng(-90.0, -180.0))->toBeInstanceOf(GeoCoordinate::class);
});

it('toWkt xuất POINT theo thứ tự (lng lat) — chuẩn GeoJSON/WKT', function () {
    $wkt = GeoCoordinate::fromLatLng(21.028511, 105.804817)->toWkt();

    expect($wkt)->toBe('POINT(105.80481700 21.02851100)');
});
