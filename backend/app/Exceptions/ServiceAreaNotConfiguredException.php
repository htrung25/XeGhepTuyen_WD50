<?php

namespace App\Exceptions;

use Exception;

class ServiceAreaNotConfiguredException extends Exception
{
    public function __construct(string $message = 'Tuyến chưa được cấu hình đầy đủ vùng phục vụ')
    {
        parent::__construct($message);
    }
}
