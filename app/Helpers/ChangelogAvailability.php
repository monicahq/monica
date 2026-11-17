<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Whether What's new exists on this instance. Switched off in configuration, it is absent
 * everywhere: no screen, no link, no admin section, no endpoint.
 */
class ChangelogAvailability
{
    public static function isEnabled(): bool
    {
        return (bool) config('monica.changelog_enabled');
    }
}
