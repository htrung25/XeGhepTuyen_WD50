<?php

namespace App\Enums;

enum SeatType: string
{
    case Standard = 'standard';
    case Vip      = 'vip';

    public function label(): string
    {
        return match($this) {
            self::Standard => 'Thường',
            self::Vip      => 'VIP',
        };
    }
}
