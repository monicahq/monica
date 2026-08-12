<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to file something into a marketplace category that
 * has been deactivated. Deactivating one stops new publications into it and
 * leaves everything already filed there reachable, so the refusal belongs on
 * the way in and nowhere else.
 */
class CategoryIsNotActive extends RuntimeException {}
