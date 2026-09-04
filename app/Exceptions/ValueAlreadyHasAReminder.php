<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a second live rule is asked for on a date that already has one.
 *
 * One live rule per value is what the whole model rests on, and it is enforced
 * here rather than by a unique index: a unique index cannot say "one *live* row"
 * under soft deletes, since the deleted ones keep the column filled in.
 */
class ValueAlreadyHasAReminder extends RuntimeException {}
