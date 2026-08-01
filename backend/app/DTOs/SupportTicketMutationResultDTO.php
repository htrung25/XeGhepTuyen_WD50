<?php

namespace App\DTOs;

use App\Models\SupportMessage;
use App\Models\SupportTicket;

final readonly class SupportTicketMutationResultDTO
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function __construct(
        public SupportTicket $ticket,
        public array $oldValues,
        public array $newValues,
        public ?SupportMessage $message = null,
    ) {}
}
