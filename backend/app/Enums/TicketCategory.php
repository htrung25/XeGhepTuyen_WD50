<?php

namespace App\Enums;

enum TicketCategory: string
{
    case General = 'general';
    case Payment = 'payment';
    case Refund = 'refund';
    case Complaint = 'complaint';
    case Technical = 'technical';
    case Other = 'other';
}
