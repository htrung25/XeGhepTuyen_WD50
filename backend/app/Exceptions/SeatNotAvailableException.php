<?php

namespace App\Exceptions;

use Exception;

class SeatNotAvailableException extends Exception
{
    public function __construct(string $message = 'Ghế đã được đặt bởi người khác')
    {
        parent::__construct($message);
    }
}
