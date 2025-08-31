<?php

namespace App\Exceptions;

use Exception;

class UnknownPermissionException extends Exception
{
    public function __construct($message = "Unknown permission", $code = 400)
    {
        parent::__construct($message, $code);
    }
}
