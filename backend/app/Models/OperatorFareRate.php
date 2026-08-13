<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Đơn giá vé của MỘT tuyến: giá vé tuyến = phí mở cửa + đơn giá/km × số km.
 * Tuyến chưa có dòng nào ⇒ chưa có giá ⇒ không lên lịch chạy được.
 */
class OperatorFareRate extends Model
{
    use HasUuids;

    protected $table = 'operator_fare_rates';

    protected $fillable = [
        'operator_id',
        'route_id',
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

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
