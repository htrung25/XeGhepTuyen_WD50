<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Pending   = 'pending';
    case Verified  = 'verified';
    case Suspended = 'suspended';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Chờ duyệt',
            self::Verified  => 'Đã duyệt',
            self::Suspended => 'Đình chỉ',
            self::Rejected  => 'Từ chối',
        };
    }
}
