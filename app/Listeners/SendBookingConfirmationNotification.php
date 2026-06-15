<?php

namespace App\Listeners;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Events\BookingConfirmed;
use App\Services\NotificationService;

class SendBookingConfirmationNotification
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking->load('trip.route', 'pickupStop');
        $user    = $booking->user;

        $departAt = $booking->trip->depart_at->format('H:i d/m/Y');
        $route    = "{$booking->trip->route->origin_city} → {$booking->trip->route->dest_city}";
        $pickup   = $booking->pickupStop->stop_name;
        $trackUrl = config('app.url') . "/track/{$booking->booking_code}";

        $body = "[XeGhep] Đặt vé thành công! Mã vé: {$booking->booking_code}\n"
              . "Tuyến: {$route}\n"
              . "Ngày: {$departAt}\n"
              . "Điểm đón: {$pickup}\n"
              . "Theo dõi: {$trackUrl}";

        $this->notificationService->send(
            $user,
            NotificationType::BookingConfirmed,
            [
                'title'      => 'Đặt vé thành công',
                'body'       => $body,
                'booking_id' => $booking->id,
            ],
            [NotificationChannel::Sms, NotificationChannel::Zalo, NotificationChannel::InApp]
        );
    }
}
