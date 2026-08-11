<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidBookingJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public readonly Booking $booking)
    {
        $this->onQueue('high');
    }

    public function handle(BookingService $bookingService): void
    {
        $this->booking->refresh();
        $bookingService->expire($this->booking);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ExpireUnpaidBookingJob thất bại', [
            'booking_id' => $this->booking->id,
            'error' => $e->getMessage(),
        ]);
    }
}
