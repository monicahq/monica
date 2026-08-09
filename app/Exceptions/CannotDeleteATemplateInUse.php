<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when somebody tries to delete a template that records still point at.
 *
 * Deleting it would leave those records with no structure to render with, so
 * the answer is to deactivate it. It then stops being offered to anything new
 * and every record already on it stays exactly as it is.
 */
class CannotDeleteATemplateInUse extends RuntimeException {}
