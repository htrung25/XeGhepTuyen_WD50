<?php

namespace App\Listeners;

use App\Events\DriverLocationUpdatedEvent;

class BroadcastDriverLocationListener
{
    // ShouldBroadcastNow trên event tự xử lý broadcast
    // Listener này dùng cho side effects nếu cần
    public function handle(DriverLocationUpdatedEvent $event): void
    {
        // Broadcast được xử lý tự động bởi ShouldBroadcastNow trên Event
        // Listener này có thể mở rộng cho analytics, ETA alerting, v.v.
    }
}
