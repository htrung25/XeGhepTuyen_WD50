<?php

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    public $timestamps = false;

    const CREATED_AT = 'sent_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'booking_id',
        'type',
        'title',
        'body',
        'data',
        'channel',
        'is_read',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => NotificationTypeEnum::class,
            'channel' => NotificationChannelEnum::class,
            'data' => 'array',
            'is_read' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInApp(Builder $query): Builder
    {
        return $query->where('channel', NotificationChannelEnum::InApp);
    }
}
