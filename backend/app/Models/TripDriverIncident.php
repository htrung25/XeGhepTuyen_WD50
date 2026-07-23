<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nhật ký sự cố "tài xế không chạy được chuyến".
 * Một chuyến có thể có NHIỀU incident theo thời gian (đổi tài xế nhiều lần),
 * nhưng tại một thời điểm tối đa 1 incident `open` (đảm bảo bằng khóa trong transaction).
 */
class TripDriverIncident extends Model
{
    use HasUuids;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public const RESOLUTION_REASSIGNED = 'reassigned';

    public const RESOLUTION_CANCELLED = 'cancelled';

    // Chuyến vẫn CHẠY dù đang treo cờ (nhà xe xác nhận chuyến quá giờ đã đi xong):
    // không phải reassigned cũng không phải cancelled → resolution riêng, tránh ghi sai.
    public const RESOLUTION_COMPLETED = 'completed';

    protected $fillable = [
        'trip_id',
        'reported_driver_id',
        'reason',
        'reported_at',
        'status',
        'resolution',
        'replacement_driver_id',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function reportedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'reported_driver_id');
    }

    public function replacementDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'replacement_driver_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
