<?php

namespace App\Exceptions;

use Exception;

class InvalidOtpException extends Exception
{
    public function __construct(string $message = 'Mã OTP không hợp lệ hoặc đã hết hạn')
    {
        parent::__construct($message);
    }
}
