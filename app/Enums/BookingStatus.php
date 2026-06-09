<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow    = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Chờ thanh toán',
            self::Confirmed => 'Đã xác nhận',
            self::CheckedIn => 'Đã check-in',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
            self::NoShow    => 'Không lên xe',
        };
    }
}
