<?php

use App\Models\ServiceArea;

// SQLite không ép kiểu: boundary lưu WKT dạng text là đủ cho test không-spatial
function makeArea(string $code, string $name, bool $active = true): ServiceArea
{
    return ServiceArea::create([
        'code' => $code,
        'name' => $name,
        'boundary' => 'MULTIPOLYGON(((0 0, 1 0, 1 1, 0 1, 0 0)))',
        'is_active' => $active,
    ]);
}

it('findByCityCode match chính xác theo mã, không phân biệt hoa thường/khoảng trắng', function () {
    makeArea('HN', 'Hà Nội');

    expect(ServiceArea::findByCityCode('HN')?->code)->toBe('HN')
        ->and(ServiceArea::findByCityCode(' hn ')?->code)->toBe('HN')
        ->and(ServiceArea::findByCityCode('HP'))->toBeNull();
});

it('findByCityCode bỏ qua vùng đã tắt', function () {
    makeArea('HN', 'Hà Nội', active: false);

    expect(ServiceArea::findByCityCode('HN'))->toBeNull();
});
