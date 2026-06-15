<?php

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Events\TripStarted;
use App\Services\NotificationService;

class NotifyPassengersOnTripStart
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(TripStarted $event): void
    {
        $trip     = $event->trip->load(['bookings.user', 'driver.user', 'vehicle']);
        $driver   = $trip->driver->user->full_name;
        $plate    = $trip->vehicle->plate_number;
        $phone    = $trip->driver->user->phone;

        $body = "[XeGhep] Chuyến của bạn đã xuất phát! Tài xế: {$driver} - {$phone}. Biển số: {$plate}";

        foreach ($trip->bookings->where('booking_status', BookingStatus::Confirmed) as $booking) {
            $this->notificationService->send(
                $booking->user,
                NotificationType::TripStarted,
                ['title' => 'Chuyến đã xuất phát', 'body' => $body, 'booking_id' => $booking->id],
                [NotificationChannel::Sms, NotificationChannel::InApp]
            );
        }
    }
}
