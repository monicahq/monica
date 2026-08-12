<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an entity is saved without a name. Almost nothing about an entity
 * is imposed, and the name is the exception: the product lists it, searches it,
 * writes it in headers and draws initials from it, so a record with none is a
 * row nothing can render.
 */
class EntityNeedsAName extends RuntimeException {}
