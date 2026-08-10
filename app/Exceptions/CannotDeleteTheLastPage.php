<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to remove the only page a template version has
 * left. A version with no page has nowhere to put a section, and nothing to
 * render.
 */
class CannotDeleteTheLastPage extends RuntimeException {}
