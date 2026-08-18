<?php

use App\Http\Resources\Admin\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;

it('trả về huyện và tỉnh của hai đầu tuyến cho danh sách chuyến admin', function () {
    makeOperatorWithRevenue(0, 0);

    $trip = Trip::query()->firstOrFail();
    $trip->route()->update([
        'origin_city' => 'Hà Nội',
        'origin_district' => 'Quận Cầu Giấy',
        'dest_city' => 'Hải Phòng',
        'dest_district' => 'Quận Hồng Bàng',
    ]);
    $trip->load(['route', 'vehicle.operator', 'driver.user']);

    $data = (new TripResource($trip))->toArray(Request::create('/api/admin/trips'));

    expect($data['route'])
        ->origin_district->toBe('Quận Cầu Giấy')
        ->dest_district->toBe('Quận Hồng Bàng');
});
