<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a contact is saved without a single part of a name. Every part is
 * optional on its own, but the product sorts, searches and builds initials out
 * of the name, so a contact with none of them is a row nothing can render.
 */
class ContactNeedsAName extends RuntimeException {}
