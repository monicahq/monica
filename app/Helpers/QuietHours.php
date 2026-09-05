<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\NotificationPreference;
use Carbon\CarbonImmutable;

class QuietHours
{
    public static function release(NotificationPreference $preference, CarbonImmutable $instant): CarbonImmutable
    {
        if (! self::hold($preference, $instant)) {
            return $instant;
        }

        $local = $instant->setTimezone($preference->timezone);
        $end = self::at($local, (string) $preference->quiet_hours_end);

        // A window that wraps midnight is still running when the day turns, so the end it reaches is
        // tomorrow's rather than the one already gone.
        if (! $end->greaterThan($local)) {
            $end = $end->addDay();
        }

        return $end->utc();
    }

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

    private static function at(CarbonImmutable $local, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map(intval(...), explode(':', mb_substr($time, 0, 5)));

        return $local->setTime($hour, $minute);
    }
}
