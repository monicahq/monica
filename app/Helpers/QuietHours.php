<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;

/**
 * When a notification may go out, given the hours somebody asked not to be
 * disturbed during.
 *
 * Quiet hours hold a notification. They never drop one, and they never move the
 * deadline it is about: something due at 3am is still recorded as having been
 * due at 3am, and only its delivery waits until 8am. Moving the deadline instead
 * would corrupt the history of when things were actually due, which is the one
 * thing the deadline exists to remember.
 *
 * The window is read in the zone on the preferences, since it is about the hours
 * of somebody's day rather than about any absolute instant.
 *
 * Crossing midnight is the case worth stating, because 22:00 to 08:00 is the
 * obvious setting and the naive comparison gets it exactly backwards: a window
 * whose start is after its end wraps, so anything at or after the start and
 * anything before the end are both inside it.
 *
 * A window with both ends the same is a whole day of quiet. It is not treated as
 * no quiet at all, which would be the product deciding somebody did not mean
 * what they saved, and nothing is lost by it: the notification waits.
 */
class QuietHours
{
    /**
     * The first instant at or after the one given that the preferences allow a
     * notification out.
     */
    public static function release(NotificationPreference $preference, CarbonImmutable $instant): CarbonImmutable
    {
        if (! self::hold($preference, $instant)) {
            return $instant;
        }

        $local = $instant->setTimezone($preference->timezone);
        $end = self::at($local, (string) $preference->quiet_hours_end);

        /* A window that wraps midnight is still running when the day turns, so
           the end it reaches is tomorrow's rather than the one already gone. */
        if (! $end->greaterThan($local)) {
            $end = $end->addDay();
        }

        return $end->utc();
    }

    /**
     * Whether the preferences forbid delivering at the instant given.
     */
    public static function hold(NotificationPreference $preference, CarbonImmutable $instant): bool
    {
        if (! $preference->quiet_hours_are_enabled) {
            return false;
        }

        if ($preference->quiet_hours_start === null || $preference->quiet_hours_end === null) {
            return false;
        }

        $local = $instant->setTimezone($preference->timezone);
        $start = self::at($local, $preference->quiet_hours_start);
        $end = self::at($local, $preference->quiet_hours_end);

        if ($start->equalTo($end)) {
            return true;
        }

        if ($start->lessThan($end)) {
            return ! $local->lessThan($start) && $local->lessThan($end);
        }

        return ! $local->lessThan($start) || $local->lessThan($end);
    }

    /**
     * One local time of day, on the day the instant falls on.
     */
    private static function at(CarbonImmutable $local, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map(intval(...), explode(':', mb_substr($time, 0, 5)));

        return $local->setTime($hour, $minute);
    }
}
