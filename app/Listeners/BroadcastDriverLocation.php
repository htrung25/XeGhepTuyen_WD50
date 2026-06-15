<?php

namespace App\Listeners;

use App\Events\DriverLocationUpdated;

class BroadcastDriverLocation
{
    // ShouldBroadcastNow trên event tự xử lý broadcast
    // Listener này dùng cho side effects nếu cần
    public function handle(DriverLocationUpdated $event): void
    {
        // Broadcast được xử lý tự động bởi ShouldBroadcastNow trên Event
        // Listener này có thể mở rộng cho analytics, ETA alerting, v.v.
    }
}
