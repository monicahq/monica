<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class PossessionTypeFileIsNotReadable extends RuntimeException
{
    /** @param  list<string>  $problems */
    public function __construct(
        public readonly array $problems,
    ) {
        parent::__construct(implode(' ', $problems));
    }
}
