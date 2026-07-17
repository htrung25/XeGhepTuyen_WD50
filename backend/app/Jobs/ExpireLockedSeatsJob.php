<?php

namespace App\Jobs;

use App\Enums\SeatStatusEnum;
use App\Models\SeatMap;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireLockedSeatsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

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
