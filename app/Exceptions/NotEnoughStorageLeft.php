<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a file would take an account past the storage it is allowed.
 *
 * The refusal happens before a byte is written rather than after. Writing the
 * file and then discovering there was no room for it leaves the disk holding
 * something the account was told it could not have.
 */
class NotEnoughStorageLeft extends RuntimeException {}
