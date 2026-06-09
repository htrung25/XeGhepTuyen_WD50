<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Topup      = 'topup';
    case Payment    = 'payment';
    case Refund     = 'refund';
    case Payout     = 'payout';
    case Commission = 'commission';

    public function label(): string
    {
        return match($this) {
            self::Topup      => 'Nạp tiền',
            self::Payment    => 'Thanh toán',
            self::Refund     => 'Hoàn tiền',
            self::Payout     => 'Rút tiền',
            self::Commission => 'Hoa hồng',
        };
    }
}
