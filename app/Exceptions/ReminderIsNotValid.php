<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ReminderIsNotValid extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?string $field = null,
    ) {
        parent::__construct($message);
    }
}
