<?php

namespace App\DTOs;

use App\Enums\DriverStatusEnum;
use App\Models\Driver;

final readonly class DriverTransitionResultDTO
{
    public function __construct(
        public Driver $driver,
        public DriverStatusEnum $oldStatus,
        public DriverStatusEnum $newStatus,
    ) {}
}
