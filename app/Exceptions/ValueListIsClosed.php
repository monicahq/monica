<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to add a choice to a list that does not take any.
 * A closed list is one whose choices mean something to the product rather than
 * only to the person reading them.
 */
class ValueListIsClosed extends RuntimeException {}
