<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody asks to be reminded of a date they have not given. A rule
 * reads one value and derives every deadline from it, so a rule over an empty
 * date would be a rule that can never fire, saved as though it would.
 */
class ReminderNeedsADate extends RuntimeException {}
