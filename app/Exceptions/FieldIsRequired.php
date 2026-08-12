<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a page is saved with a required field left empty.
 *
 * It is refused here and not one value at a time, because "required" is a
 * statement about a whole record being submitted. Writing one value cannot
 * refuse a field somebody has not reached yet.
 */
class FieldIsRequired extends RuntimeException {}
