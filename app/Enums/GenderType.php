<?php

namespace App\Enums;

enum GenderType: string
{
    case Male   = 'male';
    case Female = 'female';
    case Other  = 'other';

    public function label(): string
    {
        return match($this) {
            self::Male   => 'Nam',
            self::Female => 'Nữ',
            self::Other  => 'Khác',
        };
    }
}
