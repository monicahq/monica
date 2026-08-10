<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete a file something still points at.
 *
 * A file goes when nothing refers to it any more and the grace period has run
 * out. Taking one out from under a record would leave that record showing a
 * pointer to nothing, which is the one thing the reference count exists to
 * prevent.
 */
class CannotDeleteAFileInUse extends RuntimeException {}
