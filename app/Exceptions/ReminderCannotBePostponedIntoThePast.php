<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ReminderCannotBePostponedIntoThePast extends RuntimeException {}
