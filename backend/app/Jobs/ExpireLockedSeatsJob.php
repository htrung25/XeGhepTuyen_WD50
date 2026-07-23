<?php

namespace App\Jobs;

use App\Enums\SeatStatusEnum;
use App\Models\SeatMap;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dọn ghế giữ tạm quá 10' — scheduler bơm mỗi phút.
 *
 * ShouldBeUnique: scheduler `withoutOverlapping()` chỉ khóa TÁC VỤ DISPATCH
 * (xong trong mili-giây), KHÔNG khóa việc job được THỰC THI. Nếu worker chậm
 * hoặc không nghe queue `high`, mỗi phút lại chồng thêm 1 job → tồn hàng trăm
 * bản trùng. Unique lock đảm bảo tối đa 1 job trong hàng đợi tại một thời điểm.
 */
class ExpireLockedSeatsJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** TTL khóa unique (giây) — đủ dài để chạy xong, đủ ngắn để không kẹt vĩnh viễn nếu worker chết */
    public int $uniqueFor = 300;

    public function __construct()
    {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        $expired = SeatMap::where('status', SeatStatusEnum::Locked)
            ->where('locked_at', '<', now()->subMinutes(10))
            ->update([
                'status' => SeatStatusEnum::Available,
                'locked_at' => null,
                'locked_by' => null,
            ]);

        Log::info("ExpireLockedSeatsJob: giải phóng {$expired} ghế hết hạn");
    }
}
