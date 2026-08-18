<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Tài xế đã báo không chạy được chuyến → cần nhà xe sắp xếp lại.
 * Chỉ mang id (không mang model) để an toàn khi queue serialize + đọc lại state mới nhất.
 * `eventId` sinh 1 lần lúc dispatch → ổn định qua các lần job retry (dùng làm dedupe_key).
 * `incidentId` là id incident vừa mở → khóa dedupe DÙNG CHUNG giữa listener hàng loạt và
 * đường callback thanh toán (khách confirm muộn), tránh gửi trùng cho cùng 1 khách.
 */
class TripDriverUnavailableEvent
{
    use Dispatchable, SerializesModels;

    public readonly string $eventId;

    public function __construct(
        public readonly string $tripId,
        public readonly string $reason,
        public readonly string $reportedDriverId,
        public readonly ?string $incidentId = null,
        public readonly string $issueType = 'driver',
        ?string $eventId = null,
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }
}
