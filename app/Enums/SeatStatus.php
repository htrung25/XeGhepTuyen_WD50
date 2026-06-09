<?php

namespace App\Enums;

enum SeatStatus: string
{
    case Available = 'available';
    case Locked    = 'locked';
    case Booked    = 'booked';
    case Disabled  = 'disabled';

    public function label(): string
    {
        return match($this) {
            self::Available => 'Còn trống',
            self::Locked    => 'Đang giữ',
            self::Booked    => 'Đã đặt',
            self::Disabled  => 'Không sử dụng',
        };
    }
}
