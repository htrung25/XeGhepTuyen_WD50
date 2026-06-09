<?php

namespace App\Enums;

enum BookingPaymentStatus: string
{
    case Unpaid        = 'unpaid';
    case Paid          = 'paid';
    case Refunded      = 'refunded';
    case PartialRefund = 'partial_refund';

    public function label(): string
    {
        return match($this) {
            self::Unpaid        => 'Chưa thanh toán',
            self::Paid          => 'Đã thanh toán',
            self::Refunded      => 'Đã hoàn tiền',
            self::PartialRefund => 'Hoàn một phần',
        };
    }
}
