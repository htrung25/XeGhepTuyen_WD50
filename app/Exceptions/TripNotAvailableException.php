<?php

namespace App\Exceptions;

use Exception;

class TripNotAvailableException extends Exception
{
    public function __construct(string $message = 'Chuyến đi không còn khả dụng')
    {
        parent::__construct($message);
    }
}
