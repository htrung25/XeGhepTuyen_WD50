<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\Trip;
use App\Services\BookingService;
use App\Services\TripService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoResolveTripsCommand extends Command
{
    protected $signature = 'trips:auto-resolve';

    protected $description = 'Tự động xử lý vé/chuyến quá giờ: hủy chuyến nhà xe không chạy (hoàn tiền), tất toán chuyến đang chạy bị bỏ quên';

    /** Số giờ ân hạn sau giờ khởi hành trước khi coi nhà xe không thực hiện chuyến */
    private const SCHEDULED_GRACE_HOURS = 2;

    /** Số giờ đệm sau giờ đến dự kiến trước khi tự động hoàn tất chuyến đang chạy */
    private const IN_PROGRESS_BUFFER_HOURS = 1;

    public function handle(TripService $tripService, BookingService $bookingService): int
    {
        $now = now();

        // 1) Chuyến chưa chạy mà đã quá giờ khởi hành + ân hạn → nhà xe không thực hiện
        //    → hủy chuyến, hoàn 100% + bồi thường cho mọi vé còn hiệu lực.
        $stale = Trip::whereIn('status', [TripStatus::Scheduled, TripStatus::Boarding])
            ->where('depart_at', '<', $now->copy()->subHours(self::SCHEDULED_GRACE_HOURS))
            ->get();

        foreach ($stale as $trip) {
            try {
                $tripService->cancelTrip($trip, 'Nhà xe không thực hiện chuyến (quá giờ khởi hành)', true);
                $this->info("Đã hủy + hoàn tiền chuyến {$trip->tracking_code}");
            } catch (\Throwable $e) {
                Log::error('AutoResolve cancel failed', ['trip' => $trip->id, 'error' => $e->getMessage()]);
            }
        }

        // 2) Chuyến đang chạy quá lâu không được hoàn tất (tài xế quên bấm)
        //    → tự hoàn tất: checked_in→completed, confirmed→no_show.
        $overdue = Trip::where('status', TripStatus::InProgress)
            ->where('arrive_at', '<', $now->copy()->subHours(self::IN_PROGRESS_BUFFER_HOURS))
            ->get();

        foreach ($overdue as $trip) {
            try {
                $tripService->completeTrip($trip);
                $this->info("Đã tự hoàn tất chuyến {$trip->tracking_code}");
            } catch (\Throwable $e) {
                Log::error('AutoResolve complete failed', ['trip' => $trip->id, 'error' => $e->getMessage()]);
            }
        }

        // 3) Vé "mồ côi": còn hiệu lực nhưng chuyến đã ĐÓNG (completed/cancelled) mà chưa được tất toán.
        //    Gồm dữ liệu cũ + vé confirm khi chuyến còn hợp lệ rồi chuyến trôi qua/bị đóng.
        $orphanTrips = Trip::whereIn('status', [TripStatus::Completed, TripStatus::Cancelled])
            ->whereHas('bookings', fn ($b) => $b->whereIn('booking_status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::CheckedIn->value,
            ]))
            ->get();

        $orphanCount = 0;
        foreach ($orphanTrips as $trip) {
            try {
                if ($trip->status === TripStatus::Completed) {
                    // checked_in→completed, confirmed→no_show, pending→cancelled
                    $bookingService->finalizeOnTripComplete($trip);
                } else { // Cancelled
                    $trip->bookings()
                         ->whereIn('booking_status', [
                             BookingStatus::Pending->value,
                             BookingStatus::Confirmed->value,
                             BookingStatus::CheckedIn->value,
                         ])
                         ->with('user')->get()
                         ->each(fn (Booking $b) => $bookingService->cancelByOperator($b, 'Chuyến đã bị hủy', true));
                }
                $orphanCount++;
            } catch (\Throwable $e) {
                Log::error('AutoResolve orphan failed', ['trip' => $trip->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Xong: {$stale->count()} chuyến hủy, {$overdue->count()} chuyến hoàn tất, {$orphanCount} chuyến có vé mồ côi đã tất toán.");

        return self::SUCCESS;
    }
}
