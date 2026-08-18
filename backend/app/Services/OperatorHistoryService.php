<?php

namespace App\Services;

use App\Models\OperatorHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OperatorHistoryService
{
    /**
     * Ghi lịch sử theo kiểu best-effort: lỗi nhật ký không được làm hỏng nghiệp vụ chính.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $operatorId,
        string $category,
        string $action,
        string $title,
        ?string $description = null,
        string $severity = 'info',
        ?Model $subject = null,
        ?string $actorUserId = null,
        array $metadata = [],
        ?string $dedupeKey = null,
        mixed $occurredAt = null,
    ): ?OperatorHistory {
        try {
            $attributes = [
                'operator_id' => $operatorId,
                'actor_user_id' => $actorUserId,
                'category' => $category,
                'action' => $action,
                'severity' => $severity,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'title' => $title,
                'description' => $description,
                'metadata' => $metadata ?: null,
                'occurred_at' => $occurredAt ?? now(),
            ];

            if ($dedupeKey !== null) {
                return OperatorHistory::firstOrCreate(['dedupe_key' => $dedupeKey], $attributes);
            }

            return OperatorHistory::create($attributes);
        } catch (\Throwable $exception) {
            Log::error('Unable to write operator history', [
                'operator_id' => $operatorId,
                'action' => $action,
                'dedupe_key' => $dedupeKey,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
