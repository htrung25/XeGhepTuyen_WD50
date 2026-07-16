<?php

namespace App\Models;

use App\Enums\PartnerApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerApplication extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_name',
        'tax_code',
        'address',
        'vehicle_count',
        'fleet_breakdown',
        'representative_name',
        'phone',
        'email',
        'business_license_url',
        'fleet_images',
        'status',
        'note',
        'reviewed_by',
        'reviewed_at',
        'operator_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PartnerApplicationStatus::class,
            'fleet_images' => 'array',
            'fleet_breakdown' => 'array',
            'vehicle_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PartnerApplicationStatus::Pending);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function canApprove(): bool
    {
        return in_array($this->status, [
            PartnerApplicationStatus::Pending,
            PartnerApplicationStatus::Contacted,
        ], true);
    }

    /**
     * Nhãn các loại xe trong cơ cấu đội xe.
     *
     * @var array<string, string>
     */
    public const FLEET_LABELS = [
        'sedan_4' => 'xe 4 chỗ',
        'mpv_7' => 'xe 7 chỗ',
        'van_9' => 'xe 9 chỗ (limousine)',
        'minibus_16' => 'xe 16 chỗ',
    ];

    /**
     * Tóm tắt cơ cấu đội xe đã khai, vd: "2 xe 4 chỗ · 3 xe 7 chỗ · 1 xe 9 chỗ (limousine)".
     */
    public function fleetSummary(): string
    {
        $breakdown = $this->fleet_breakdown ?? [];

        $parts = [];
        foreach (self::FLEET_LABELS as $key => $label) {
            $qty = (int) ($breakdown[$key] ?? 0);
            if ($qty > 0) {
                $parts[] = "{$qty} {$label}";
            }
        }

        return empty($parts) ? '—' : implode(' · ', $parts);
    }
}
