<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown if the given value is not in the list of authorized values.
 */
class WrongValueException extends RuntimeException
{
}
