<?php

declare(strict_types=1);

namespace App\Helpers;

class PasskeyAvailability
{
    public const string NOT_SECURE = 'not_secure';

    public static function onThisInstance(): bool
    {
        return self::reason() === null;
    }

    public static function reason(): ?string
    {
        if (! self::isSecureContext()) {
            return self::NOT_SECURE;
        }

        return null;
    }

    private static function isSecureContext(): bool
    {
        return request()->secure();
    }
}
