<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidBookingsCommand extends Command
{
    protected $signature = 'bookings:expire-unpaid {--limit=500 : Số booking tối đa xử lý mỗi lần}';

    protected $description = 'Hủy booking quá hạn thanh toán và giải phóng ghế tương ứng';

    public function handle(BookingService $bookingService): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $bookingIds = Booking::query()
            ->expired()
            ->orderBy('expires_at')
            ->limit($limit)
            ->pluck('id');

        $expired = 0;
        $failed = 0;

        foreach ($bookingIds as $bookingId) {
            try {
                $booking = Booking::find($bookingId);
                if (! $booking) {
                    continue;
                }

                $bookingService->expire($booking);
                $expired++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Expire unpaid booking sweep failed', [
                    'booking_id' => $bookingId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Đã xử lý {$expired} booking quá hạn; lỗi: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
