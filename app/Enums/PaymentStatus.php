<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending  = 'pending';
    case Success  = 'success';
    case Failed   = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Đang xử lý',
            self::Success  => 'Thành công',
            self::Failed   => 'Thất bại',
            self::Refunded => 'Đã hoàn tiền',
        };
    }
}
