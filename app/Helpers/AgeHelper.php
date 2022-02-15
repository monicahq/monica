<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\User;

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

    /**
     * A contact date can be complex (see self::getAge() documentation).
     * This method returns the date as a string.
     *
     * @param  string  $date
     * @param  User  $user
     * @return string|null
     */
    public static function formatDate(string $date, User $user): ?string
    {
        if (! $date) {
            return null;
        }

        switch (strlen($date)) {
            case 10:
                // case: full date
                $string = Carbon::parse($date)->isoFormat($user->date_format);
                break;

            case 5:
                // case: only know the month and day.
                // in this case, we'll add a random year and format the date
                // with only month and day
                $date = '1900-'.$date;
                $date = Carbon::parse($date);
                switch ($user->date_format) {
                    case 'MMM DD, YYYY':
                        $string = Carbon::parse($date)->isoFormat('MMM DD');
                        break;

                    case 'DD MMM YYYY':
                        $string = Carbon::parse($date)->isoFormat('DD MMM');
                        break;

                    case 'YYYY/MM/DD':
                        $string = Carbon::parse($date)->isoFormat('MM/DD');
                        break;

                    case 'DD/MM/YYYY':
                        $string = Carbon::parse($date)->isoFormat('DD/MM');
                        break;

                    default:
                        $string = Carbon::parse($date)->isoFormat('DD/MM');
                        break;
                }
                break;

            case 4:
                // case: only know the year.
                // in this case, we'll add a random month and day and format
                // the date
                $string = $date;
                break;

            default:
                $string = $date;
                break;
        }

        return $string;
    }
}
