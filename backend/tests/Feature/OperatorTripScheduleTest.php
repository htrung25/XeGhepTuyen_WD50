<?php

use App\Enums\DriverStatusEnum;
use App\Enums\UserRoleEnum;
use App\Http\Resources\Operator\TripResource;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripService;
use Illuminate\Http\Request;

function setupTripTestEntities(): array
{
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id,
        'company_name' => 'Nhà xe Hải Âu',
        'business_license' => 'HA-123456',
        'status' => 'verified',
    ]);

    // Tuyến HN -> HP (dài 150 phút)
    $routeHnHp = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hà Nội → Hải Phòng',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'distance_km' => 105,
        'est_duration_min' => 150,
        'base_price' => 120000,
        'is_active' => true,
    ]);
    RouteStop::create(['route_id' => $routeHnHp->id, 'stop_name' => 'Bến xe Nước Ngầm', 'address' => 'HN', 'lat' => 21, 'lng' => 105, 'stop_order' => 1]);

    // Tuyến HP -> HN (dài 150 phút)
    $routeHpHn = Route::create([
        'operator_id' => $operator->id,
        'name' => 'Hải Phòng → Hà Nội',
        'origin_city' => 'Hải Phòng',
        'dest_city' => 'Hà Nội',
        'distance_km' => 105,
        'est_duration_min' => 150,
        'base_price' => 120000,
        'is_active' => true,
    ]);
    RouteStop::create(['route_id' => $routeHpHn->id, 'stop_name' => 'Bến xe Cầu Rào', 'address' => 'HP', 'lat' => 20, 'lng' => 106, 'stop_order' => 1]);

    $vehicle = Vehicle::create([
        'operator_id' => $operator->id,
        'plate_number' => '29A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford',
        'model' => 'Transit',
        'vehicle_type' => 'van_9',
        'seat_count' => 9,
    ]);

    $drvUser = User::factory()->create(['role' => UserRoleEnum::Driver]);
    $driver = Driver::create([
        'user_id' => $drvUser->id,
        'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'),
        'license_class' => 'D',
        'license_expiry' => now()->addYears(3),
        'id_card_number' => fake()->numerify('############'),
        'status' => DriverStatusEnum::Verified,
    ]);

    return [
        'operator' => $operator,
        'routeHnHp' => $routeHnHp,
        'routeHpHn' => $routeHpHn,
        'vehicle' => $vehicle,
        'driver' => $driver,
    ];
}

it('allows creating first trip when no preceding/succeeding schedules exist', function () {
    $entities = setupTripTestEntities();
    $service = app(TripService::class);

    $departAt = now()->addHours(2);
    $trip = $service->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $departAt->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    expect($trip)->toBeInstanceOf(Trip::class);
    expect($trip->depart_at->toDateTimeString())->toBe($departAt->startOfSecond()->toDateTimeString());
    expect($trip->seatMaps()->orderBy('seat_code')->pluck('seat_code')->all())->toBe([
        'A1', 'A2',
        'B1', 'B2',
        'B3', 'B4',
        'C1', 'C2', 'C3',
    ]);
});

it('rejects creating a trip with a route owned by another operator', function () {
    $entities = setupTripTestEntities();
    $otherOperator = Operator::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Operator])->id,
        'company_name' => 'Nhà xe khác',
        'business_license' => 'OTHER-ROUTE',
        'status' => 'verified',
    ]);
    $foreignRoute = Route::create([
        'operator_id' => $otherOperator->id,
        'name' => 'Tuyến của nhà xe khác',
        'origin_city' => 'Hà Nội',
        'dest_city' => 'Hải Phòng',
        'base_price' => 120000,
    ]);

    expect(fn () => app(TripService::class)->create([
        'route_id' => $foreignRoute->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => now()->addHours(2)->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]))->toThrow(InvalidArgumentException::class, 'Tuyến đường không thuộc nhà xe của bạn');
});

it('allows creating trip when preceding trip ends at the same city with at least 30 mins rest', function () {
    $entities = setupTripTestEntities();
    $service = app(TripService::class);

    // Chuyến 1: HN -> HP (09:00 -> 11:30)
    $depart1 = now()->addDays(2)->setTime(9, 0);
    $service->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart1->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    // Chuyến 2: HP -> HN (12:00 -> 14:30) -> Giản cách đúng 30 phút, vị trí khớp
    $depart2 = now()->addDays(2)->setTime(12, 0);
    $trip2 = $service->create([
        'route_id' => $entities['routeHpHn']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart2->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    expect($trip2)->toBeInstanceOf(Trip::class);
});

it('fails to create trip when driver is in different city (location mismatch)', function () {
    $entities = setupTripTestEntities();
    $service = app(TripService::class);

    // Chuyến 1: HN -> HP (09:00 -> 11:30) -> Tài xế kết thúc ở Hải Phòng
    $depart1 = now()->addDays(2)->setTime(9, 0);
    $service->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart1->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    // Chuyến 2: HN -> HP (12:00 -> 14:30) -> Vị trí xuất phát HN không trùng với HP của chuyến trước
    $depart2 = now()->addDays(2)->setTime(12, 0);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Tài xế đang ở Hải Phòng sau chuyến trước, không thể xuất phát từ Hà Nội');

    $service->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart2->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);
});

it('fails to create trip when buffer time is less than 30 minutes', function () {
    $entities = setupTripTestEntities();
    $service = app(TripService::class);

    // Chuyến 1: HN -> HP (09:00 -> 11:30)
    $depart1 = now()->addDays(2)->setTime(9, 0);
    $service->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart1->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    // Chuyến 2: HP -> HN (11:59 -> 14:29) -> Giản cách 29 phút (thiếu 1 phút)
    $depart2 = now()->addDays(2)->setTime(11, 59);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Thời gian nghỉ của tài xế giữa 2 chuyến phải tối thiểu 30 phút');

    $service->create([
        'route_id' => $entities['routeHpHn']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart2->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);
});

it('fails to insert a trip between two existing trips if succeeding trip buffer time is violated', function () {
    $entities = setupTripTestEntities();
    $service = app(TripService::class);

    // Chuyến 1: HN -> HP (09:00 -> 11:30)
    $depart1 = now()->addDays(2)->setTime(9, 0);
    $service->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart1->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    // Chuyến 3: HN -> HP (15:00 -> 17:30)
    // Tài xế cần về lại HN trước. Hãy tạo chuyến sau khứ hồi từ HP -> HN
    $depart3 = now()->addDays(2)->setTime(15, 0);
    $service->create([
        'route_id' => $entities['routeHpHn']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart3->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);

    // Bây giờ chèn chuyến 2 ở giữa: HP -> HN (12:00 -> 14:30)
    // Chuyến 2 kết thúc ở HN lúc 14:30. Chuyến 3 bắt đầu từ Hải Phòng lúc 15:00.
    // Lỗi: Chuyến tiếp theo bắt đầu từ Hải Phòng nhưng chuyến chèn kết thúc tại Hà Nội!
    $depart2 = now()->addDays(2)->setTime(12, 0);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Chuyến tiếp theo của tài xế bắt đầu từ Hải Phòng, nhưng chuyến này kết thúc tại Hà Nội');

    $service->create([
        'route_id' => $entities['routeHpHn']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => $depart2->toDateTimeString(),
        'price' => 120000,
        'operator_id' => $entities['operator']->id,
    ]);
});

it('chặn lên lịch chạy khi tuyến chưa được cấu hình giá vé', function () {
    $entities = setupTripTestEntities();
    // Tuyến tạo trước, giá vé cấu hình sau ⇒ base_price = 0 cho tới khi nhà xe
    // lưu bảng giá. Chốt chặn nằm ở bước lên lịch, không phải lúc tạo tuyến.
    $entities['routeHnHp']->update(['base_price' => 0]);

    app(TripService::class)->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => now()->addHours(2)->toDateTimeString(),
        'operator_id' => $entities['operator']->id,
    ]);
})->throws(InvalidArgumentException::class, 'chưa được cấu hình giá vé');

it('giá vé chuyến lấy từ tuyến, bỏ qua giá client gửi lên', function () {
    $entities = setupTripTestEntities();
    $entities['routeHnHp']->update(['base_price' => 175000]);

    $trip = app(TripService::class)->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => now()->addHours(2)->toDateTimeString(),
        'price' => 999000, // client cố nhập giá khác — phải bị bỏ qua
        'operator_id' => $entities['operator']->id,
    ]);

    expect((int) $trip->price)->toBe(175000);
});

it('trả về tên tuyến trong dữ liệu chi tiết chuyến của nhà xe', function () {
    $entities = setupTripTestEntities();
    $entities['routeHnHp']->update(['name' => 'Mỹ Đình → Bến xe Cầu Rào']);

    $trip = app(TripService::class)->create([
        'route_id' => $entities['routeHnHp']->id,
        'vehicle_id' => $entities['vehicle']->id,
        'driver_id' => $entities['driver']->id,
        'depart_at' => now()->addHours(2)->toDateTimeString(),
        'operator_id' => $entities['operator']->id,
    ])->load(['route', 'vehicle', 'driver.user']);

    $data = (new TripResource($trip))->toArray(Request::create('/api/operator/trips/'.$trip->id));

    expect($data['route']['name'])->toBe('Mỹ Đình → Bến xe Cầu Rào');
});
