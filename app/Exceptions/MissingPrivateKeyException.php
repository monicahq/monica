<?php

namespace App\Exceptions;

use RuntimeException;

class MissingPrivateKeyException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'No private encryption key has been specified.')
    {
        parent::__construct($message);
    }
}
