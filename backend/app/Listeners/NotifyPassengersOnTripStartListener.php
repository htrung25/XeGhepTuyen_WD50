<?php

namespace App\Listeners;

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\TripStartedEvent;
use App\Services\NotificationService;

class NotifyPassengersOnTripStartListener
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(TripStartedEvent $event): void
    {
        $trip = $event->trip->load(['bookings.user', 'driver.user', 'vehicle']);
        $driver = $trip->driver->user->full_name;
        $plate = $trip->vehicle->plate_number;
        $phone = $trip->driver->user->phone;

        $body = "[XeGhepTuyen-Fgroup] Chuyến của bạn đã xuất phát! Tài xế: {$driver} - {$phone}. Biển số: {$plate}";

        foreach ($trip->bookings->where('booking_status', BookingStatusEnum::Confirmed) as $booking) {
            $this->notificationService->send(
                $booking->user,
                NotificationTypeEnum::TripStartedEvent,
                ['title' => 'Chuyến đã xuất phát', 'body' => $body, 'booking_id' => $booking->id],
                [NotificationChannelEnum::Sms, NotificationChannelEnum::InApp]
            );
        }
    }
}
