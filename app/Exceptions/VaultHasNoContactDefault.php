<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a contact is written down without a version and the vault has no
 * template for new contacts to fall back on. Every vault is seeded with one, so
 * this is an account whose seeding never finished rather than an ordinary path.
 */
class VaultHasNoContactDefault extends RuntimeException {}
