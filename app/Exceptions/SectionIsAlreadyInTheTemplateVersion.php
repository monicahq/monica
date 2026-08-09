<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to place the same section version twice in one
 * template version.
 *
 * The same section on one template version twice would give two fields the same
 * identity, and a value written into either of them would have nowhere to go.
 * Somebody who genuinely wants the section twice wants two sections, not one
 * placed twice.
 */
class SectionIsAlreadyInTheTemplateVersion extends RuntimeException {}
