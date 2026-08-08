<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when something tries to change or remove a log entry. The journal says
 * what happened, and it is only worth reading if nothing can go back and edit
 * it afterwards.
 */
class LogIsAppendOnly extends RuntimeException {}
