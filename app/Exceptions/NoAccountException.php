<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown if the env variable is not set or does not exist.
 */
class NoAccountException extends RuntimeException
{
}
