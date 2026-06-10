<?php

namespace App\Exceptions;

use Exception;

class PaymentVerificationException extends Exception
{
    public function __construct(string $message = 'Xác thực thanh toán thất bại')
    {
        parent::__construct($message);
    }
}
