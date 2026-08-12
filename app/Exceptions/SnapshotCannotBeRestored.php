<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the structure a snapshot came from is no longer there to put the
 * record back on. The product says so rather than restoring something partial:
 * half a record put back is worse than a record left alone, because nobody can
 * tell which half.
 */
class SnapshotCannotBeRestored extends RuntimeException {}
