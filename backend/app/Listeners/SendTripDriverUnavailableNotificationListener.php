<?php

namespace App\Listeners;

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Events\TripDriverUnavailableEvent;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Tài xế báo không chạy được → báo nhà xe (cần sắp xếp lại) + hành khách đã cam kết chỗ.
 * Queued: gửi hàng loạt không chặn request. Dedupe theo eventId (ổn định qua retry).
 */
class SendTripDriverUnavailableNotificationListener implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(TripDriverUnavailableEvent $event): void
    {
        $trip = Trip::with(['route.operator.user'])->find($event->tripId);
        if (! $trip) {
            return;
        }

        // Đọc lại state mới nhất; nếu chuyến đã được xử lý xong (không còn cờ) vẫn gửi vì
        // event đã phát hợp lệ lúc báo — thông tin "đang sắp xếp lại" đúng tại thời điểm đó.
        $reportedDriver = Driver::with('user')->find($event->reportedDriverId);
        $driverName = $reportedDriver?->user?->full_name ?? 'Tài xế';
        $route = "{$trip->route->origin_city} → {$trip->route->dest_city}";
        $when = $trip->depart_at->format('H:i d/m');
        $type = NotificationTypeEnum::TripDriverUnavailable;
        $channels = [NotificationChannelEnum::InApp, NotificationChannelEnum::Sms];

        // Khóa dedupe theo INCIDENT (không theo eventId) → khách confirm muộn được đường
        // callback thanh toán gửi thông báo với CÙNG khóa incident → không trùng. Incident
        // thứ 2 trên cùng chuyến có id riêng → vẫn gửi được (không bị nuốt). Fallback eventId
        // nếu vì lý do nào đó thiếu incidentId.
        $seed = $event->incidentId ?? $event->eventId;

        // → Nhà xe
        $operatorUser = $trip->route->operator?->user;
        if ($operatorUser) {
            $this->notificationService->send(
                $operatorUser,
                $type,
                [
                    'title' => 'Tài xế báo không chạy được chuyến',
                    'body' => "Tài xế {$driverName} báo không chạy được chuyến {$route} {$when} — lý do: {$event->reason}. Cần sắp xếp lại tài xế.",
                    'kind' => 'trip_driver_unavailable',
                    'trip_id' => $trip->id,
                    'link' => "/operator/trips?trip={$trip->id}",
                ],
                $channels,
                "tdu:{$seed}:{$operatorUser->id}:{$type->value}",
            );
        }

        // → Hành khách đã cam kết chỗ (confirmed / checked_in). KHÔNG gửi pending.
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
                    'title' => 'Chuyến đang sắp xếp lại tài xế',
                    'body' => "Chuyến {$route} {$when} đang được sắp xếp lại tài xế. Vé của bạn vẫn được giữ, chúng tôi sẽ cập nhật sớm.",
                    'booking_id' => $booking->id,
                ],
                $channels,
                "tdu:{$seed}:{$booking->user->id}:{$type->value}",
            );
        }
    }
}
