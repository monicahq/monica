<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to put a repeater inside another repeater.
 *
 * A repeater is already the hardest field to render in a way that works with a
 * keyboard and a screen reader, since the number of inputs changes while
 * somebody is filling them in. Nesting one inside another compounds that for no
 * stated need, so a repeater holds ordinary fields and nothing else.
 */
class CannotNestARepeater extends RuntimeException {}
