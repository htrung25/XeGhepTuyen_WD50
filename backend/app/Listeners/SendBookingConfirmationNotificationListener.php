<?php

namespace App\Listeners;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\BookingConfirmedEvent;
use App\Services\NotificationService;

class SendBookingConfirmationNotificationListener
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(BookingConfirmedEvent $event): void
    {
        $booking = $event->booking->load('trip.route', 'pickupStop');
        $user = $booking->user;

        $departAt = $booking->trip->depart_at->format('H:i d/m/Y');
        $route = "{$booking->trip->route->origin_city} → {$booking->trip->route->dest_city}";
        $pickup = $booking->pickupStop ? $booking->pickupStop->stop_name : $booking->pickup_address;
        $trackUrl = config('app.url')."/track/{$booking->booking_code}";

        $body = "[XeGhep] Đặt vé thành công! Mã vé: {$booking->booking_code}\n"
              ."Tuyến: {$route}\n"
              ."Ngày: {$departAt}\n"
              ."Điểm đón: {$pickup}\n"
              ."Theo dõi: {$trackUrl}";

        $this->notificationService->send(
            $user,
            NotificationTypeEnum::BookingConfirmedEvent,
            [
                'title' => 'Đặt vé thành công',
                'body' => $body,
                'booking_id' => $booking->id,
            ],
            [NotificationChannelEnum::Sms, NotificationChannelEnum::Zalo, NotificationChannelEnum::InApp]
        );
    }
}
