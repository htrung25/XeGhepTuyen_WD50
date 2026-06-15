<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRefundJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 300;

    public function __construct(
        public readonly Booking $booking,
        public readonly int $refundAmount,
    ) {
        $this->onQueue('high');
    }

    public function handle(PaymentService $paymentService): void
    {
        $paymentService->refund($this->booking, $this->refundAmount);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessRefundJob thất bại', [
            'booking_id'    => $this->booking->id,
            'refund_amount' => $this->refundAmount,
            'error'         => $e->getMessage(),
        ]);
    }
}
