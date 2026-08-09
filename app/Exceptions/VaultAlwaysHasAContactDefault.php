<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to take away the template new contacts start from
 * in a vault. Every vault has one, so it can be replaced but never removed.
 */
class VaultAlwaysHasAContactDefault extends RuntimeException {}
