<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Nhà xe đã đổi tài xế cho chuyến đang chờ sắp xếp lại → chuyến chạy bình thường.
 * eventId ổn định qua retry (dedupe). Incident thứ 2 trên cùng trip có eventId riêng
 * → notification không bị dedupe nhầm với incident trước.
 */
class TripDriverReassignedEvent
{
    use Dispatchable, SerializesModels;

    public readonly string $eventId;

    public function __construct(
        public readonly string $tripId,
        public readonly string $oldDriverId,
        public readonly string $newDriverId,
        ?string $eventId = null,
        public readonly ?string $actorUserId = null,
    ) {
        $this->eventId = $eventId ?? (string) Str::uuid();
    }
}
