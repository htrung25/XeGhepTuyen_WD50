<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Push  = 'push';
    case Sms   = 'sms';
    case Zalo  = 'zalo';
    case Email = 'email';
    case InApp = 'in_app';

    public function label(): string
    {
        return match($this) {
            self::Push  => 'Push notification',
            self::Sms   => 'SMS',
            self::Zalo  => 'Zalo',
            self::Email => 'Email',
            self::InApp => 'Trong ứng dụng',
        };
    }
}
