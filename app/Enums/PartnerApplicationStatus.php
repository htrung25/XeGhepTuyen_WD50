<?php

namespace App\Enums;

enum PartnerApplicationStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý',
            self::Contacted => 'Đã liên hệ',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
        };
    }
}
