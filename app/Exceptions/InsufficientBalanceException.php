<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Số dư ví không đủ để thực hiện giao dịch')
    {
        parent::__construct($message);
    }
}
