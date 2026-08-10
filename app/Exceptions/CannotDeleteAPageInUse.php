<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to remove a page that still holds sections.
 *
 * Taking the page would take everything on it, which is a bigger act than
 * removing a tab. The sections are moved to another page or taken off the
 * template deliberately first, so nothing disappears as a side effect.
 */
class CannotDeleteAPageInUse extends RuntimeException {}
