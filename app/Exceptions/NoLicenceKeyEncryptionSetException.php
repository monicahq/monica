<?php

namespace App\Exceptions;

use RuntimeException;

class NoLicenceKeyEncryptionSetException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Customer portal\'s Licence Key is not set.')
    {
        parent::__construct($message);
    }
}
