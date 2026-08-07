<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * The single use codes somebody falls back on when their authenticator app is
 * on a phone they no longer have.
 *
 * They are minted when two factor authentication is turned on and again
 * whenever somebody asks for a fresh set, which is why the shape of them lives
 * here rather than in either action.
 */
class RecoveryCodes
{
    /**
     * How many somebody gets, and how long each one is. Eight is enough to lose
     * a few and still get in, and few enough to write on a card.
     */
    private const int COUNT = 8;

    private const int LENGTH = 10;

    /**
     * @return array<int, string>
     */
    public static function generate(): array
    {
        return collect()
            ->times(self::COUNT, fn (): string => Str::random(self::LENGTH))
            ->all();
    }
}
