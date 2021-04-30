<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown if the account has reach its.
 */
class AccountLimitException extends RuntimeException
{
}
