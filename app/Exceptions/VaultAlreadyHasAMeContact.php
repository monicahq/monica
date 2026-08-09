<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a second contact representing the user is created in a vault. One
 * vault holds exactly one of them, and everything that reads "me" would have to
 * pick between two.
 */
class VaultAlreadyHasAMeContact extends RuntimeException {}
