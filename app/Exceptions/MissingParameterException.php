<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception MissingParameterException.
 */
class MissingParameterException extends RuntimeException
{
    /**
     * The validation errors.
     *
     * @var array
     */
    public $errors;

    public function __construct(string $message, array $errors = null, int $code = 0, \Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }
}
