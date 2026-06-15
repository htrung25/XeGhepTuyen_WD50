<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateQrCodeJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Booking $booking)
    {
        $this->onQueue('default');
    }

    public function handle(QrCodeService $qrCodeService): void
    {
        $qrUrl = $qrCodeService->generate($this->booking);

        $this->booking->update(['qr_code' => $qrUrl]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateQrCodeJob thất bại', [
            'booking_id' => $this->booking->id,
            'error'      => $e->getMessage(),
        ]);
    }
}
