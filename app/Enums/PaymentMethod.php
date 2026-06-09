<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Momo    = 'momo';
    case Vnpay   = 'vnpay';
    case Zalopay = 'zalopay';
    case Wallet  = 'wallet';
    case Cash    = 'cash';

    public function label(): string
    {
        return match($this) {
            self::Momo    => 'MoMo',
            self::Vnpay   => 'VNPay',
            self::Zalopay => 'ZaloPay',
            self::Wallet  => 'Ví XeGhep',
            self::Cash    => 'Tiền mặt',
        };
    }
}
