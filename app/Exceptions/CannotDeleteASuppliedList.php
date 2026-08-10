<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete a list the product supplied. Those lists
 * are what the product itself reads from, so they stay. Everything else about
 * them is still the user's: renaming them, reordering them, and emptying them
 * of every value they were seeded with.
 */
class CannotDeleteASuppliedList extends RuntimeException {}
