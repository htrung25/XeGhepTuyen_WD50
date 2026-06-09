<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Driver   = 'driver';
    case Operator = 'operator';
    case Admin    = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Customer => 'Khách hàng',
            self::Driver   => 'Tài xế',
            self::Operator => 'Nhà xe',
            self::Admin    => 'Quản trị viên',
        };
    }
}
