<?php

namespace App\Exceptions;

use RuntimeException;

class CustomerPortalWrongCredentials extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Wrong credentials for customer portal.')
    {
        parent::__construct($message);
    }
}
