<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Whether this instance can do passkeys at all, and why not when it cannot.
 *
 * WebAuthn requires a secure connection, so that is the whole of the test. No
 * host is exempted, not even localhost, and no configured address is trusted
 * over what the request actually is. The rule stays this blunt on purpose: it is
 * what FR-040 leans on when it refuses to let somebody give up their password,
 * and a gate with exceptions in it is a gate that eventually lets that through
 * on an instance where a passkey cannot be used.
 *
 * Developing the feature therefore needs https, which Herd and Valet both
 * provide for a local host.
 *
 * The browser checks too, and has the last word, since only it knows what the
 * device in front of the person can do.
 */
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
