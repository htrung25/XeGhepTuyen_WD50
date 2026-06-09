<?php

namespace App\Enums;

enum TripStatus: string
{
    case Scheduled   = 'scheduled';
    case Boarding    = 'boarding';
    case InProgress  = 'in_progress';
    case Completed   = 'completed';
    case Cancelled   = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Scheduled  => 'Đã lên lịch',
            self::Boarding   => 'Đang đón khách',
            self::InProgress => 'Đang chạy',
            self::Completed  => 'Hoàn thành',
            self::Cancelled  => 'Đã hủy',
        };
    }
}
