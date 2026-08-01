<?php

namespace App\DTOs;

use Carbon\CarbonImmutable;

final readonly class OtpVerificationProofDTO
{
    public function __construct(
        public string $plainToken,
        public CarbonImmutable $expiresAt,
    ) {}
}
