<?php

namespace App\Exceptions;

use RuntimeException;

class NoCustomerPortalSetException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Customer portal url is not set.')
    {
        parent::__construct($message);
    }
}
