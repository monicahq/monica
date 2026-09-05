<?php

declare(strict_types=1);

namespace App\Contracts;

interface Translatable
{
    /** @return list<string> */
    public function translationKeys(): array;
}
