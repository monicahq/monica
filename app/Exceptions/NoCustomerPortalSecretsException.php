<?php

namespace App\Exceptions;

use RuntimeException;

class NoCustomerPortalSecretsException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Credentials for customer portal are not set.')
    {
        parent::__construct($message);
    }
}
