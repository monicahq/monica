<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete the only vault an account has left. An
 * account with no vault has nowhere to put a contact, so the last one stays.
 */
class CannotDeleteTheLastVault extends RuntimeException {}
