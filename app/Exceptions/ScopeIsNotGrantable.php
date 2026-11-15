<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ScopeIsNotGrantable extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $scope,
    ) {
        parent::__construct($message);
    }
}
