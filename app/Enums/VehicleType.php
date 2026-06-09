<?php

namespace App\Enums;

enum VehicleType: string
{
    case Sedan4   = 'sedan_4';
    case Mpv7     = 'mpv_7';
    case Van9     = 'van_9';
    case Minibus16 = 'minibus_16';

    public function label(): string
    {
        return match($this) {
            self::Sedan4    => 'Sedan 4 chỗ',
            self::Mpv7      => 'MPV 7 chỗ',
            self::Van9      => 'Van 9 chỗ',
            self::Minibus16 => 'Minibus 16 chỗ',
        };
    }

    public function seatCount(): int
    {
        return match($this) {
            self::Sedan4    => 4,
            self::Mpv7      => 7,
            self::Van9      => 9,
            self::Minibus16 => 16,
        };
    }
}
