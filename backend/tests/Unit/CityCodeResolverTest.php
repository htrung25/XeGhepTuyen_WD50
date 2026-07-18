<?php

use App\Services\CityCodeResolver;

it('resolve mọi biến thể tên Hà Nội về HN', function (string $city) {
    expect(CityCodeResolver::resolve($city))->toBe('HN');
})->with(['Hà Nội', 'ha noi', 'Ha Noi', 'Hanoi', 'HANOI', 'TP. Hà Nội', 'TP Hà Nội', 'Thành phố Hà Nội', '  Hà Nội  ']);

it('resolve mọi biến thể tên Hải Phòng về HP', function (string $city) {
    expect(CityCodeResolver::resolve($city))->toBe('HP');
})->with(['Hải Phòng', 'hai phong', 'Haiphong', 'TP. Hải Phòng', 'Thành phố Hải Phòng']);

it('trả null cho thành phố không hỗ trợ hoặc chuỗi rỗng', function (string $city) {
    expect(CityCodeResolver::resolve($city))->toBeNull();
})->with(['Đà Nẵng', 'Huế', '', '   ', 'H']);
