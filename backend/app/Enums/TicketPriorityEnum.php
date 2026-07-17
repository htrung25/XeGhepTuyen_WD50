<?php

namespace App\Enums;

enum TicketPriorityEnum: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
}
