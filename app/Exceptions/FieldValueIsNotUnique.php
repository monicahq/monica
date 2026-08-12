<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a field marked as unique is given a value another record in the
 * same vault already holds. The uniqueness is per vault, since a vault is a
 * compartment and two of them know nothing about each other.
 */
class FieldValueIsNotUnique extends RuntimeException {}
