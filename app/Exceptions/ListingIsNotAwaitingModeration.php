<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a decision is taken about a version nobody is waiting on: one
 * already approved, already refused, or pulled by its author in the meantime.
 */
class ListingIsNotAwaitingModeration extends RuntimeException {}
