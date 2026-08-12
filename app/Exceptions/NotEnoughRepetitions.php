<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when removing a repetition would leave a repeater with fewer than the
 * field asks for.
 */
class NotEnoughRepetitions extends RuntimeException {}
