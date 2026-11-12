<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Whether support exists on this instance, and whether anybody runs it who could read a request.
 * Switched off in configuration, support is absent everywhere. Without an active administrator a
 * request would wait for nobody, so asking for help is not offered either.
 */
class SupportAvailability
{
    public static function isEnabled(): bool
    {
        return (bool) config('monica.support_enabled');
    }

    public static function isOffered(): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        return self::administratorQuery()->exists();
    }

    /**
     * The people who answer support requests on this instance, by name.
     *
     * @return Collection<int, User>
     */
    public static function administrators(): Collection
    {
        return self::administratorQuery()
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();
    }

    /** @return Builder<User> */
    private static function administratorQuery(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('administrator');
    }
}
