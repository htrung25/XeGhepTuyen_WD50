<?php

use App\Jobs\ExpireLockedSeatsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động xử lý vé/chuyến quá giờ (hủy chuyến nhà xe không chạy + tất toán chuyến bỏ quên)
Schedule::command('trips:auto-resolve')->everyTenMinutes()->withoutOverlapping();

// Giải phóng ghế giữ tạm quá 10' (dọn DB; tầng đọc đã tự liền qua SeatMap::isAvailable)
Schedule::job(new ExpireLockedSeatsJob)->everyMinute()->withoutOverlapping();
