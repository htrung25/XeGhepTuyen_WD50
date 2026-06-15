<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\Review;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateDriverRatingJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $driverId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $driver = Driver::find($this->driverId);
        if (!$driver) {
            return;
        }

        $avgRating = Review::where('driver_id', $this->driverId)
                           ->where('is_published', true)
                           ->avg(\Illuminate\Support\Facades\DB::raw('(driver_rating + vehicle_rating + service_rating) / 3'));

        $totalTrips = Review::where('driver_id', $this->driverId)->count();

        $driver->update([
            'rating_avg'  => round($avgRating ?? 5.00, 2),
            'total_trips' => $totalTrips,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('UpdateDriverRatingJob thất bại', [
            'driver_id' => $this->driverId,
            'error'     => $e->getMessage(),
        ]);
    }
}
