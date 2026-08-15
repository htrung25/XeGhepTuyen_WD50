<?php

use App\Models\Booking;
use App\Models\Operator;
use App\Models\Trip;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * R5: daily/byRoute/byDriver gộp bằng Collection (bỏ raw SQL). R6: validate kỳ custom.
 * Helper makeOperatorWithRevenue() định nghĩa ở tests/Pest.php (global).
 */
function opHeaders(Operator $operator): array
{
    return ['Authorization' => 'Bearer '.$operator->user->createToken('operator_token')->plainTextToken];
}

function revCustomRange(): string
{
    return '?period=custom&from_date='.now()->subDays(2)->toDateString().'&to_date='.now()->toDateString();
}

it('daily gộp theo ngày chạy (không raw SQL)', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 1); // 2 vé cùng 1 chuyến (1 ngày)
    $data = $this->getJson('/api/operator/revenue/daily'.revCustomRange(), opHeaders($operator))
        ->assertOk()->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['total_bookings'])->toBe(2);
    expect($data[0]['revenue'])->toBe(300000);
});

it('transactions trả chi tiết doanh thu theo chuyến và có phân trang', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 1);

    $this->getJson('/api/operator/revenue/transactions'.revCustomRange(), opHeaders($operator))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.route', 'Hà Nội → Hải Phòng')
        ->assertJsonPath('data.0.passengers', 2)
        ->assertJsonPath('data.0.daily_passengers', 2)
        ->assertJsonPath('data.0.seat_count', 9)
        ->assertJsonPath('data.0.gross_revenue', 300000)
        ->assertJsonPath('data.0.commission', 30000)
        ->assertJsonPath('data.0.net_revenue', 270000);
});

it('transactions tính tổng khách của cả ngày cho mọi chuyến cùng ngày', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);
    $firstTrip = Trip::whereHas('vehicle', fn ($q) => $q->where('operator_id', $operator->id))->firstOrFail();

    $secondTrip = $firstTrip->replicate();
    $secondTrip->depart_at = $firstTrip->depart_at->copy()->addHour();
    $secondTrip->save();

    $secondBooking = Booking::where('trip_id', $firstTrip->id)->firstOrFail()->replicate();
    $secondBooking->trip_id = $secondTrip->id;
    $secondBooking->booking_code = 'DAY'.Str::upper(Str::random(8));
    $secondBooking->qr_token = Str::random(32);
    $secondBooking->passenger_count = 2;
    $secondBooking->save();

    $rows = $this->getJson('/api/operator/revenue/transactions'.revCustomRange(), opHeaders($operator))
        ->assertOk()
        ->json('data');

    expect($rows)->toHaveCount(2);
    expect(array_column($rows, 'daily_passengers'))->each->toBe(3);
});

it('byRoute gộp theo tuyến', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 1);
    $data = $this->getJson('/api/operator/revenue/by-route'.revCustomRange(), opHeaders($operator))
        ->assertOk()->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Hà Nội → Hải Phòng');
    expect($data[0]['revenue'])->toBe(300000);
});

it('byDriver gộp theo tài xế', function () {
    $operator = makeOperatorWithRevenue(online: 2, cash: 0);
    $data = $this->getJson('/api/operator/revenue/by-driver'.revCustomRange(), opHeaders($operator))
        ->assertOk()->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['total_bookings'])->toBe(2);
    expect($data[0]['revenue'])->toBe(300000);
});

it('kỳ custom thiếu from_date → 422', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);

    $this->getJson('/api/operator/revenue/summary?period=custom', opHeaders($operator))
        ->assertStatus(422);
});

it('summary kỳ cố định (month) lấy từ cache-aside Redis', function () {
    $operator = makeOperatorWithRevenue(online: 1, cash: 0);
    // Seed cache giá trị giả → endpoint trả đúng giá trị cached (không tính lại).
    Cache::put("operator:{$operator->id}:revenue:summary:month", ['gross_revenue' => 777, 'period' => 'month'], 60);

    $this->getJson('/api/operator/revenue/summary?period=month', opHeaders($operator))
        ->assertOk()
        ->assertJsonPath('data.gross_revenue', 777);
});
