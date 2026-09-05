<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

class RecoveryCodes
{
    private const int COUNT = 8;

    private const int LENGTH = 10;

    /** @return array<int, string> */
    public static function generate(): array
    {
        return collect()
            ->times(self::COUNT, fn (): string => Str::random(self::LENGTH))
            ->all();
    }
}
