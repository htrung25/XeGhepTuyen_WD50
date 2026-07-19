<?php

use App\DTOs\GeoCoordinate;
use App\DTOs\GeometryExpression;
use App\Services\GeometryFactory;

it('point() sinh biểu thức có binding, SRID 4326 và axis-order=long-lat', function () {
    $expr = new GeometryFactory()->point(GeoCoordinate::fromLatLng(21.028511, 105.804817));

    expect($expr)->toBeInstanceOf(GeometryExpression::class)
        ->and($expr->sql)->toContain('?')
        ->and($expr->sql)->toContain('4326')
        ->and($expr->sql)->toContain('axis-order=long-lat')
        ->and($expr->bindings)->toBe(['POINT(105.80481700 21.02851100)']);
});

it('fromWkt() bind nguyên văn WKT, không nối chuỗi vào SQL', function () {
    $wkt = 'MULTIPOLYGON(((105.3 20.5, 106.0 20.5, 106.0 21.4, 105.3 21.4, 105.3 20.5)))';
    $expr = new GeometryFactory()->fromWkt($wkt);

    expect($expr->sql)->not->toContain('MULTIPOLYGON')
        ->and($expr->bindings)->toBe([$wkt]);
});

it('wktFromGeoJsonGeometry convert Polygon → MULTIPOLYGON WKT giữ nguyên thứ tự lng-lat', function () {
    $geometry = [
        'type' => 'Polygon',
        'coordinates' => [[[105.3, 20.5], [106.0, 20.5], [106.0, 21.4], [105.3, 20.5]]],
    ];

    expect(new GeometryFactory()->wktFromGeoJsonGeometry($geometry))
        ->toBe('MULTIPOLYGON(((105.3 20.5, 106 20.5, 106 21.4, 105.3 20.5)))');
});

it('wktFromGeoJsonGeometry convert MultiPolygon nhiều ring/đảo', function () {
    $geometry = [
        'type' => 'MultiPolygon',
        'coordinates' => [
            [[[0, 0], [1, 0], [1, 1], [0, 0]]],
            [[[5, 5], [6, 5], [6, 6], [5, 5]]],
        ],
    ];

    expect(new GeometryFactory()->wktFromGeoJsonGeometry($geometry))
        ->toBe('MULTIPOLYGON(((0 0, 1 0, 1 1, 0 0)), ((5 5, 6 5, 6 6, 5 5)))');
});

it('wktFromGeoJsonGeometry từ chối type khác Polygon/MultiPolygon', function () {
    new GeometryFactory()->wktFromGeoJsonGeometry(['type' => 'Point', 'coordinates' => [1, 2]]);
})->throws(InvalidArgumentException::class);
