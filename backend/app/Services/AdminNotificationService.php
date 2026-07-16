<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\User;

/**
 * Tạo thông báo in-app cho admin theo quyền (RBAC fan-out).
 *
 * Mỗi sự kiện chỉ gửi cho các admin CÓ QUYỀN xử lý tương ứng (vd yêu cầu quyết toán
 * → admin có finance.payout). Lưu vào bảng `notifications` sẵn có: type=system,
 * data = {kind, link, ...} để FE hiển thị icon + điều hướng tới trang xử lý.
 */
class AdminNotificationService
{
    public function notify(string $permission, string $title, string $body, array $data = []): void
    {
        $admins = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_active', true)
            ->with('adminRole')
            ->get()
            ->filter(fn (User $admin): bool => $admin->hasPermission($permission));

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => NotificationType::System,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'channel' => NotificationChannel::InApp,
                'is_read' => false,
                'sent_at' => now(),
            ]);
        }
    }
}
