<?php

namespace App\Services;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendSmsNotificationJob;
use App\Jobs\SendZaloNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\QueryException;

class NotificationService
{
    /**
     * Gửi thông báo qua các kênh — luôn dispatch Job, không gọi API trực tiếp.
     *
     * @param  string|null  $dedupeKey  Khóa chống trùng (thường "{event_id}:{user_id}:{type}").
     *                                  Khi có, mỗi kênh gắn thêm ":{channel}" và ghi vào cột
     *                                  UNIQUE `dedupe_key`; nếu trùng (job retry) → bỏ qua ở tầng DB.
     */
    public function send(User $user, NotificationTypeEnum $type, array $data, array $channels = [], ?string $dedupeKey = null): void
    {
        if (empty($channels)) {
            $channels = [NotificationChannelEnum::InApp, NotificationChannelEnum::Sms];
        }

        $title = $data['title'] ?? $type->label();
        $body = $data['body'] ?? '';
        $bookingId = $data['booking_id'] ?? null;

        foreach ($channels as $channel) {
            $channelKey = $dedupeKey ? "{$dedupeKey}:{$channel->value}" : null;

            // Chống trùng ở TẦNG DB: nếu insert đụng unique(dedupe_key) → coi như đã gửi,
            // bỏ qua cả việc dispatch job cho kênh này.
            if (! $this->saveToDb($user, $type, $title, $body, $channel, $bookingId, $data, $channelKey)) {
                continue;
            }

            $this->dispatchJob($user, $channel, $body, $data);
        }
    }

    private function saveToDb(User $user, NotificationTypeEnum $type, string $title, string $body, NotificationChannelEnum $channel, ?string $bookingId, array $data, ?string $dedupeKey = null): bool
    {
        try {
            Notification::create([
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'channel' => $channel,
                'is_read' => false,
                'dedupe_key' => $dedupeKey,
            ]);

            return true;
        } catch (QueryException $e) {
            // 23000 = integrity constraint violation (trùng dedupe_key) → đã gửi rồi, bỏ qua.
            if ($dedupeKey !== null && $this->isUniqueViolation($e)) {
                return false;
            }
            throw $e;
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // CHỈ nuốt lỗi khi đúng là vi phạm UNIQUE (trùng dedupe_key) — KHÔNG nuốt các
        // integrity violation khác cùng lớp SQLSTATE 23000 (FK, NOT NULL...) kẻo mất
        // lỗi thật. MySQL: driver code 1062 = ER_DUP_ENTRY. SQLite: chuỗi đặc trưng.
        $driverCode = $e->errorInfo[1] ?? null;

        return $driverCode === 1062
            || str_contains($e->getMessage(), 'UNIQUE constraint failed');
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
