<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a reminder cannot be built from what was given.
 *
 * A field whose type holds no date, a value with no date in it, a rhythm nobody
 * could mean, a zone the product does not know and a local time that is not a
 * time are all reasons a rule could never fire. Refusing is what stops a rule
 * being stored that quietly never does anything.
 */
class ReminderIsNotValid extends RuntimeException {}
