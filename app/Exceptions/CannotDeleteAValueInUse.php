<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete a choice that records already point at.
 * Deleting it would change what those records say, so the answer is to stop
 * offering it instead, which leaves every one of them readable.
 */
class CannotDeleteAValueInUse extends RuntimeException {}
