<?php

namespace App\Exceptions;

use Exception;

class EnvVariablesNotSetException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Env variables are not set for the service.')
    {
        parent::__construct($message);
    }
}
