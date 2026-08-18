<?php

use App\Http\Resources\Customer\BookingResource as CustomerBookingResource;
use App\Http\Resources\Driver\TripResource as DriverTripResource;
use App\Http\Resources\Operator\BookingResource as OperatorBookingResource;
use App\Models\Booking;
use App\Models\Trip;
use Illuminate\Http\Request;

it('trả dữ liệu huyện và tỉnh cho các màn chuyến của tài xế nhà xe và khách hàng', function () {
    makeOperatorWithRevenue(1, 0);

    $trip = Trip::query()->firstOrFail();
    $trip->route()->update([
        'origin_city' => 'Hà Nội',
        'origin_district' => 'Quận Cầu Giấy',
        'dest_city' => 'Hải Phòng',
        'dest_district' => 'Quận Hồng Bàng',
    ]);
    $trip->load(['route', 'vehicle.operator.user']);

    $booking = Booking::query()->firstOrFail()->load([
        'trip.route',
        'trip.vehicle',
        'trip.driver.user',
        'passengers.seatMap',
        'pickupStop',
        'dropoffStop',
    ]);
    $request = Request::create('/api/trips');

    $driverData = (new DriverTripResource($trip))->toArray($request);
    $operatorData = (new OperatorBookingResource($booking))->toArray($request);
    $customerData = (new CustomerBookingResource($booking))->toArray($request);

    expect($driverData['route']['origin_district'])->toBe('Quận Cầu Giấy')
        ->and($driverData['route']['dest_district'])->toBe('Quận Hồng Bàng')
        ->and($operatorData['trip']['route']['origin_district'])->toBe('Quận Cầu Giấy')
        ->and($operatorData['trip']['route']['dest_district'])->toBe('Quận Hồng Bàng')
        ->and($customerData['trip']['route'])->toBe('Quận Cầu Giấy, Hà Nội → Quận Hồng Bàng, Hải Phòng');
});
