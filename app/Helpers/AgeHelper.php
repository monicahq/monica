<?php

namespace App\Helpers;

use Carbon\Carbon;

class AgeHelper
{
    /**
     * Age in Monica is complex because in real life, we don't always know the
     * exact dates of the persons we meet.
     * Therefore, we need some clever ways of storing that in the DB.
     * Right now, dates that can be age are stored as a string in the database.
     * There are a bunch of possible use cases:
     * - we know the exact date - so the field is a complete date,
     * - we only know the age,
     * - we only know the day and month.
     *
     * @param  string  $date
     * @return string|null
     */
    public static function getAge(string $date): ?string
    {
        if (! $date) {
            return null;
        }

        // case: full date
        if (strlen($date) == 10) {
            $age = Carbon::parse($date)->age;
        }

        // case: only know the age. In this case, we have stored a year.
        if (strlen($date) == 4) {
            $age = Carbon::createFromFormat('Y', $date)->age;
        }

        // case: only know the month and day.
        if (strlen($date) == 5) {
            return null;
        }

        return $age;
    }
}
