<?php

namespace App\Exceptions;

use RuntimeException;

class RateLimitedSecondException extends RuntimeException
{
    public function __construct($e)
    {
        parent::__construct('', 429, $e);
    }
}
