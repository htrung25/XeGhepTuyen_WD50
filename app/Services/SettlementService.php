<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Trip;

/**
 * Đối soát quyết toán nhà xe có phân biệt phương thức thanh toán.
 *
 * Nguyên tắc (Phương án A — nhà xe trả tài xế):
 *  - Nền tảng LUÔN hưởng hoa hồng trên mọi vé realized (online + tiền mặt).
 *  - Online : nền tảng GIỮ tiền ⇒ nền tảng NỢ nhà xe (final − hoa hồng).
 *  - Tiền mặt: tài xế/nhà xe ĐÃ thu tiền ⇒ nhà xe NỢ nền tảng phần hoa hồng.
 *  - settlement = Σonline(final − hoa hồng) − Σcash(hoa hồng)
 *      > 0 ⇒ nền tảng còn phải chi cho nhà xe
 *      < 0 ⇒ nhà xe phải nộp lại nền tảng (hoa hồng tiền mặt vượt phần online)
 *
 * Nguồn tính DUY NHẤT, dùng chung cho Operator/RevenueController và
 * Admin/FinanceController để hai portal không bao giờ lệch số.
 */
class SettlementService
{
    /** Trạng thái vé tính là doanh thu THỰC NHẬN */
    private const REALIZED = ['booking_status' => 'completed', 'payment_status' => 'paid'];

    /**
     * @param  iterable|null  $tripIds  Giới hạn trên tập chuyến (null = mọi chuyến của nhà xe)
     * @return array{gross:int,online_gross:int,cash_gross:int,online_commission:int,cash_commission:int,commission:int,online_net:int,settlement:int}
     */
    public function forOperator(string $operatorId, float $rate, ?iterable $tripIds = null): array
    {
        $tripIds ??= Trip::whereHas('vehicle', fn ($q) => $q->where('operator_id', $operatorId))->pluck('id');

        $base = Booking::whereIn('trip_id', $tripIds)
            ->where('booking_status', self::REALIZED['booking_status'])
            ->where('payment_status', self::REALIZED['payment_status']);

        $onlineGross = (int) (clone $base)->where('payment_method', '!=', 'cash')->sum('final_amount');
        $cashGross = (int) (clone $base)->where('payment_method', 'cash')->sum('final_amount');

        $onlineCommission = (int) round($onlineGross * $rate / 100);
        $cashCommission = (int) round($cashGross * $rate / 100);
        $onlineNet = $onlineGross - $onlineCommission;

        return [
            'gross' => $onlineGross + $cashGross,
            'online_gross' => $onlineGross,
            'cash_gross' => $cashGross,
            'online_commission' => $onlineCommission,
            'cash_commission' => $cashCommission,
            'commission' => $onlineCommission + $cashCommission,
            'online_net' => $onlineNet,
            'settlement' => $onlineNet - $cashCommission,
        ];
    }
}
