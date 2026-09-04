<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody puts a deadline off to a moment that has already gone.
 *
 * Putting something off means being told about it later. A deadline moved
 * backwards would either fire immediately or never, and neither is what anybody
 * asked for.
 */
class ReminderCannotBePostponedIntoThePast extends RuntimeException {}
