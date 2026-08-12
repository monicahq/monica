<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a record of a given type is created in a vault that has no
 * structure for that type to start from. A type belongs to the account and is
 * visible everywhere; whether it is used in a given vault is decided there, by
 * whether a default template is set for it.
 */
class VaultHasNoEntityDefault extends RuntimeException {}
