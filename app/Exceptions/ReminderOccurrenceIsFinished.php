<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody acts on a deadline that is already done with.
 *
 * Dismissing something already closed or putting off something cancelled asks
 * a deadline to leave a state it has settled into. Refusing keeps the record of
 * what happened to it honest, which is the only reason the row is still there.
 */
class ReminderOccurrenceIsFinished extends RuntimeException {}
