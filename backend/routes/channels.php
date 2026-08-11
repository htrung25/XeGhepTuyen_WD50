<?php

use App\Models\Booking;
use App\Models\SupportTicket;
use App\Models\Trip;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// PresenceChannel: hành khách tham gia kênh chuyến đi để nhận real-time
Broadcast::channel('trips.{tripId}', function ($user, string $tripId) {
    $trip = Trip::find($tripId);

    if (! $trip) {
        return false;
    }

    // Driver của chuyến
    if ($trip->driver?->user_id === $user->id) {
        return ['id' => $user->id, 'name' => $user->full_name, 'role' => 'driver'];
    }

    // Khách có booking confirmed/checked_in cho chuyến này
    $hasBooking = Booking::where('trip_id', $tripId)
        ->where('user_id', $user->id)
        ->whereIn('booking_status', ['confirmed', 'checked_in'])
        ->exists();

    if ($hasBooking) {
        return ['id' => $user->id, 'name' => $user->full_name, 'role' => 'customer'];
    }

    return false;
});

// PrivateChannel: admin monitor board nhận vị trí tài xế
Broadcast::channel('admin.monitor', function ($user) {
    return $user->role->value === 'admin';
});

// PrivateChannel: thông báo cá nhân cho từng user
Broadcast::channel('users.{userId}', function ($user, string $userId) {
    return $user->id === $userId;
});

// Room hội thoại công khai của một ticket: chỉ chủ ticket hoặc admin có quyền xem.
Broadcast::channel('support.tickets.{ticketId}', function ($user, string $ticketId) {
    if ($user->hasPermission('support_tickets.view')) {
        return true;
    }

    return SupportTicket::whereKey($ticketId)
        ->where('user_id', $user->id)
        ->exists();
}, ['guards' => ['sanctum']]);

// Kênh ghi chú nội bộ tuyệt đối không cho customer tham gia.
Broadcast::channel('admin.support.tickets.{ticketId}', function ($user, string $ticketId) {
    return $user->hasPermission('support_tickets.view')
        && SupportTicket::whereKey($ticketId)->exists();
}, ['guards' => ['sanctum']]);

// Feed realtime danh sách ticket dành cho bộ phận hỗ trợ.
Broadcast::channel('admin.support', function ($user) {
    return $user->hasPermission('support_tickets.view');
}, ['guards' => ['sanctum']]);
