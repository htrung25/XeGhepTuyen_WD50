<?php

namespace App\Jobs\Booking;

use App\Enums\SeatStatusEnum;
use App\Models\SeatMap;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Release seats that have remained locked for more than ten minutes.
 */
class ExpireLockedSeatsJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

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

        if ($expired > 0) {
            Log::info("ExpireLockedSeatsJob: giải phóng {$expired} ghế hết hạn");
        }
    }
}
