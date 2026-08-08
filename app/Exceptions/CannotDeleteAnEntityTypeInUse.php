<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete a kind of object that records still
 * exist for. Deleting it would take those records with it, so the answer is to
 * delete them first and mean it, or to deactivate the type and keep them.
 */
class CannotDeleteAnEntityTypeInUse extends RuntimeException {}
