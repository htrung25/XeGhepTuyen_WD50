<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một dòng bảng giá của nhà xe. Phạm vi áp dụng theo độ ưu tiên:
 * (tỉnh + huyện) > (tỉnh) > (mặc định nhà xe). Xem FarePricingService.
 */
class OperatorFareRate extends Model
{
    use HasUuids;

    protected $table = 'operator_fare_rates';

    protected $fillable = [
        'operator_id',
        'province_code',
        'district_code',
        'province_name',
        'district_name',
        'base_fare',
        'price_per_km',
    ];

    protected function casts(): array
    {
        return [
            'base_fare' => 'integer',
            'price_per_km' => 'float',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /** Nhãn hiển thị phạm vi áp dụng của dòng giá */
    public function scopeLabel(): string
    {
        if ($this->district_name && $this->province_name) {
            return "{$this->district_name}, {$this->province_name}";
        }

        return $this->province_name ? "Toàn bộ {$this->province_name}" : 'Mặc định (mọi tuyến)';
    }
}
