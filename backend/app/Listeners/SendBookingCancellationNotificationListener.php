<?php

namespace App\Listeners;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\BookingCancelledEvent;
use App\Services\NotificationService;

class SendBookingCancellationNotificationListener
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(BookingCancelledEvent $event): void
    {
        $booking = $event->booking;
        $refundAmount = $event->refundAmount;
        $user = $booking->user;

        $body = $refundAmount > 0
            ? "[XeGhep] Vé {$booking->booking_code} đã hủy. Hoàn tiền ".number_format($refundAmount, 0, ',', '.').'đ sẽ về ví trong 3-5 ngày làm việc'
            : "[XeGhep] Vé {$booking->booking_code} đã hủy. Không có hoàn tiền do hủy trong vòng 4 giờ trước giờ xuất phát.";

        $this->notificationService->send(
            $user,
            NotificationTypeEnum::BookingCancelledEvent,
            ['title' => 'Hủy vé thành công', 'body' => $body, 'booking_id' => $booking->id],
            [NotificationChannelEnum::Sms, NotificationChannelEnum::InApp]
        );
    }
}
