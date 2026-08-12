<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a repetition is added to a repeater that already holds as many as
 * the field allows.
 */
class TooManyRepetitions extends RuntimeException {}
