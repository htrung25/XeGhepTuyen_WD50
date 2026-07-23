<?php

namespace App\Listeners;

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\TripDriverReassignedEvent;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Nhà xe đã đổi tài xế → báo hành khách (chuyến chạy bình thường) + tài xế mới (được phân công)
 * + tài xế cũ (đã chuyển chuyến). Queued. Dedupe theo eventId (incident thứ 2 có eventId riêng).
 */
class SendTripDriverReassignedNotificationListener implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(TripDriverReassignedEvent $event): void
    {
        $trip = Trip::with(['route'])->find($event->tripId);
        if (! $trip) {
            return;
        }

        $route = "{$trip->route->origin_city} → {$trip->route->dest_city}";
        $when = $trip->depart_at->format('H:i d/m');
        $type = NotificationTypeEnum::TripDriverReassigned;
        $channels = [NotificationChannelEnum::InApp, NotificationChannelEnum::Sms];

        // → Hành khách đã cam kết chỗ
        $bookings = $trip->bookings()
            ->whereIn('booking_status', [BookingStatusEnum::Confirmed->value, BookingStatusEnum::CheckedIn->value])
            ->with('user')
            ->get();

        foreach ($bookings as $booking) {
            if (! $booking->user) {
                continue;
            }
            $this->notificationService->send(
                $booking->user,
                $type,
                [
                    'title' => 'Chuyến đã sắp xếp tài xế',
                    'body' => "Chuyến {$route} {$when} đã sắp xếp tài xế và chạy bình thường.",
                    'booking_id' => $booking->id,
                ],
                $channels,
                "{$event->eventId}:{$booking->user->id}:{$type->value}",
            );
        }

        // → Tài xế mới
        $newDriver = Driver::with('user')->find($event->newDriverId);
        if ($newDriver?->user) {
            $this->notificationService->send(
                $newDriver->user,
                $type,
                [
                    'title' => 'Bạn được phân công chuyến mới',
                    'body' => "Bạn được phân công chuyến {$route} {$when}.",
                    'trip_id' => $trip->id,
                ],
                $channels,
                "{$event->eventId}:{$newDriver->user->id}:{$type->value}",
            );
        }

        // → Tài xế cũ
        $oldDriver = Driver::with('user')->find($event->oldDriverId);
        if ($oldDriver?->user) {
            $this->notificationService->send(
                $oldDriver->user,
                $type,
                [
                    'title' => 'Chuyến đã chuyển cho tài xế khác',
                    'body' => "Chuyến {$route} {$when} đã được chuyển cho tài xế khác.",
                    'trip_id' => $trip->id,
                ],
                $channels,
                "{$event->eventId}:{$oldDriver->user->id}:{$type->value}",
            );
        }
    }
}
