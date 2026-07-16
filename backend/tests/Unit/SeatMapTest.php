<?php

use App\Enums\SeatStatus;
use App\Models\SeatMap;
use Tests\TestCase;

// Cần boot Laravel app cho cast datetime (locked_at); không cần DB nên không RefreshDatabase.
uses(TestCase::class);

/**
 * P3: ghế Locked quá 10' tự coi là available ở tầng đọc (không phụ thuộc job dọn).
 */
it('ghế Locked quá 10 phút coi như available', function () {
    $seat = new SeatMap(['status' => SeatStatus::Locked, 'locked_at' => now()->subMinutes(11)]);

    expect($seat->isLockExpired())->toBeTrue();
    expect($seat->isAvailable())->toBeTrue();
});

it('ghế Locked còn trong 10 phút thì CHƯA available', function () {
    $seat = new SeatMap(['status' => SeatStatus::Locked, 'locked_at' => now()->subMinutes(5)]);

    expect($seat->isAvailable())->toBeFalse();
});

it('ghế Available luôn available; ghế Booked thì không', function () {
    expect((new SeatMap(['status' => SeatStatus::Available]))->isAvailable())->toBeTrue();
    expect((new SeatMap(['status' => SeatStatus::Booked]))->isAvailable())->toBeFalse();
});
