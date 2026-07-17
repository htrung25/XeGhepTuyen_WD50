<?php

use App\Enums\UserRoleEnum;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TripService;

/**
 * Gap Redis: cache tìm chuyến (trips:search:*) được vô hiệu hoá khi tạo/hủy chuyến
 * (generation counter) — chuyến mới xuất hiện ngay, không chờ TTL 2'.
 */
it('tạo chuyến làm mới cache tìm chuyến (search phản ánh ngay)', function () {
    $opUser = User::factory()->create(['role' => UserRoleEnum::Operator]);
    $operator = Operator::create([
        'user_id' => $opUser->id, 'company_name' => 'NX', 'business_license' => 'GP', 'status' => 'verified',
    ]);
    $route = Route::create([
        'operator_id' => $operator->id, 'name' => 'HN→HP',
        'origin_city' => 'Hà Nội', 'dest_city' => 'Hải Phòng', 'base_price' => 150000,
    ]);
    $vehicle = Vehicle::create([
        'operator_id' => $operator->id, 'plate_number' => '30A-'.fake()->unique()->numerify('#####'),
        'brand' => 'Ford', 'model' => 'Transit', 'vehicle_type' => 'van_9', 'seat_count' => 9,
    ]);
    $driver = Driver::create([
        'user_id' => User::factory()->create(['role' => UserRoleEnum::Driver])->id, 'operator_id' => $operator->id,
        'license_number' => 'B2-'.fake()->unique()->numerify('######'), 'license_class' => 'B2',
        'license_expiry' => now()->addYears(3), 'id_card_number' => fake()->numerify('############'), 'status' => 'verified',
    ]);

    $svc = app(TripService::class);
    $date = now()->addDay()->toDateString();
    $filters = ['from_city' => 'Hà Nội', 'to_city' => 'Hải Phòng', 'date' => $date, 'passengers' => 1];

    // Lần 1: chưa có chuyến → rỗng (và kết quả rỗng này được cache).
    expect($svc->search($filters))->toHaveCount(0);

    // Tạo chuyến cho đúng ngày đó → flush cache tìm chuyến.
    $svc->create([
        'route_id' => $route->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        'depart_at' => now()->addDay()->setTime(10, 0), 'price' => 150000, 'operator_id' => $operator->id,
    ]);

    // Lần 2: phải thấy chuyến mới NGAY (nếu không invalidate, cache rỗng cũ sẽ còn 2').
    expect($svc->search($filters))->toHaveCount(1);
});
