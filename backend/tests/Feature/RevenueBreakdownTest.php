<?php

use App\Models\Operator;
use Illuminate\Support\Facades\Cache;

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
