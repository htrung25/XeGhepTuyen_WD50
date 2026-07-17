<?php

namespace App\DTOs;

use App\Enums\DriverStatus;
use App\Models\Driver;

final readonly class DriverTransitionResult
{
    public function __construct(
        public Driver $driver,
        public DriverStatus $oldStatus,
        public DriverStatus $newStatus,
    ) {}
}
