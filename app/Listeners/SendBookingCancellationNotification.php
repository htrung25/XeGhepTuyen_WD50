<?php

namespace App\Listeners;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Events\BookingCancelled;
use App\Services\NotificationService;

class SendBookingCancellationNotification
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(BookingCancelled $event): void
    {
        $booking      = $event->booking;
        $refundAmount = $event->refundAmount;
        $user         = $booking->user;

        $body = $refundAmount > 0
            ? "[XeGhep] Vé {$booking->booking_code} đã hủy. Hoàn tiền " . number_format($refundAmount, 0, ',', '.') . "đ sẽ về ví trong 3-5 ngày làm việc"
            : "[XeGhep] Vé {$booking->booking_code} đã hủy. Không có hoàn tiền do hủy trong vòng 4 giờ trước giờ xuất phát.";

        $this->notificationService->send(
            $user,
            NotificationType::BookingCancelled,
            ['title' => 'Hủy vé thành công', 'body' => $body, 'booking_id' => $booking->id],
            [NotificationChannel::Sms, NotificationChannel::InApp]
        );
    }
}
