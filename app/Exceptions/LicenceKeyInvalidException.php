<?php

namespace App\Exceptions;

use RuntimeException;

class LicenceKeyInvalidException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = '')
    {
        parent::__construct($message !== '' ? $message : trans('settings.subscriptions_licence_key_invalid'));
    }
}
