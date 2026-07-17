<?php

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendSmsNotificationJob;
use App\Jobs\SendZaloNotificationJob;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Gửi thông báo qua các kênh — luôn dispatch Job, không gọi API trực tiếp
     */
    public function send(User $user, NotificationTypeEnum $type, array $data, array $channels = []): void
    {
        if (empty($channels)) {
            $channels = [NotificationChannelEnum::InApp, NotificationChannelEnum::Sms];
        }

        $title = $data['title'] ?? $type->label();
        $body = $data['body'] ?? '';
        $bookingId = $data['booking_id'] ?? null;

        foreach ($channels as $channel) {
            $this->saveToDb($user, $type, $title, $body, $channel, $bookingId, $data);
            $this->dispatchJob($user, $channel, $body, $data);
        }
    }

    private function saveToDb(User $user, NotificationTypeEnum $type, string $title, string $body, NotificationChannelEnum $channel, ?string $bookingId, array $data): void
    {
        Notification::create([
            'user_id' => $user->id,
            'booking_id' => $bookingId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'channel' => $channel,
            'is_read' => false,
        ]);
    }

    private function dispatchJob(User $user, NotificationChannelEnum $channel, string $message, array $data): void
    {
        match ($channel) {
            NotificationChannelEnum::Sms => SendSmsNotificationJob::dispatch(
                $user->phone, $message, $data['booking_id'] ?? null
            )->onQueue('notifications'),
            NotificationChannelEnum::Zalo => SendZaloNotificationJob::dispatch(
                $user->zalo_user_id, $message, $data
            )->onQueue('notifications'),
            NotificationChannelEnum::Email => $user->email ? SendEmailNotificationJob::dispatch(
                $user->email, $data['title'] ?? '', $message, $data
            )->onQueue('notifications') : null,
            default => null,
        };
    }

    public function markAsRead(string $userId, ?string $notificationId = null): void
    {
        $query = Notification::where('user_id', $userId)->where('is_read', false);

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        $query->update(['is_read' => true]);
    }
}
