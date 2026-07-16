<?php

namespace App\Exceptions;

use Exception;

class BookingExpiredException extends Exception
{
    public function __construct(string $message = 'Đơn đặt vé đã hết hạn thanh toán')
    {
        parent::__construct($message);
    }
}
