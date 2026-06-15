<?php

namespace App\Jobs;

use App\Enums\SeatStatus;
use App\Models\SeatMap;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireLockedSeatsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('high');
    }

    public function handle(): void
    {
        $expired = SeatMap::where('status', SeatStatus::Locked)
                          ->where('locked_at', '<', now()->subMinutes(10))
                          ->update([
                              'status'    => SeatStatus::Available,
                              'locked_at' => null,
                              'locked_by' => null,
                          ]);

        Log::info("ExpireLockedSeatsJob: giải phóng {$expired} ghế hết hạn");
    }
}
