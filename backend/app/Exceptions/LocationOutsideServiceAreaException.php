<?php

namespace App\Exceptions;

use Exception;

class LocationOutsideServiceAreaException extends Exception
{
    public function __construct(string $message = 'Điểm đón/trả nằm ngoài vùng phục vụ của tuyến')
    {
        parent::__construct($message);
    }
}
