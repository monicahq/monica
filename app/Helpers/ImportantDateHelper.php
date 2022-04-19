<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ContactImportantDate;

class ImportantDateHelper
{
    /**
     * Get the age represented by a Contact Important Date.
     *
     * @param  ContactImportantDate  $date
     * @return int|null
     */
    public static function getAge(ContactImportantDate $date): ?int
    {
        $age = 0;

        if (self::determineType($date) === ContactImportantDate::TYPE_FULL_DATE) {
            $age = Carbon::parse($date->year.'-'.$date->month.'-'.$date->day)->age;
        }

        if (self::determineType($date) === ContactImportantDate::TYPE_YEAR) {
            $age = Carbon::createFromFormat('Y', $date->year)->age;
        }

        if (self::determineType($date) === ContactImportantDate::TYPE_MONTH_DAY) {
            return null;
        }

        // case: date is empty. this shouldn't happen, but we'll cover the case
        // nonetheless
        if (! $date->day && ! $date->month && ! $date->year) {
            return null;
        }

        return $age;
    }

    /**
     * Get the date as a string, based on the prefered date format of the given
     * user.
     *
     * @param  ContactImportantDate  $date
     * @param  User  $user
     * @return string|null
     */
    public static function formatDate(ContactImportantDate $date, User $user): ?string
    {
        $dateAsString = '';

        if (self::determineType($date) === ContactImportantDate::TYPE_FULL_DATE) {
            $dateAsString = Carbon::parse($date->year.'-'.$date->month.'-'.$date->day)->isoFormat($user->date_format);
        }

        if (self::determineType($date) === ContactImportantDate::TYPE_YEAR) {
            $dateAsString = $date->year;
        }

        if (self::determineType($date) === ContactImportantDate::TYPE_MONTH_DAY) {
            $date = Carbon::parse('1900-'.$date->month.'-'.$date->day);

            $dateAsString = match ($user->date_format) {
                'MMM DD, YYYY' => Carbon::parse($date)->isoFormat('MMM DD'),
                'DD MMM YYYY' => Carbon::parse($date)->isoFormat('DD MMM'),
                'YYYY/MM/DD' => Carbon::parse($date)->isoFormat('MM/DD'),
                default => Carbon::parse($date)->isoFormat('DD/MM'),
            };
        }

        // case: date is empty. this shouldn't happen, but we'll cover the case
        // nonetheless
        if (! $date->day && ! $date->month && ! $date->year) {
            return null;
        }

        return $dateAsString;
    }

    /**
     * Determine the type of the given date.
     * The type can be:
     * - fullDate
     * - monthDay
     * - year.
     *
     * @param  ContactImportantDate  $date
     * @return string|null
     */
    public static function determineType(ContactImportantDate $date): ?string
    {
        $type = '';

        // case: date is empty. this shouldn't happen, but we'll cover the case
        // nonetheless
        if (! $date->day && ! $date->month && ! $date->year) {
            return null;
        }

        // case: full date
        if ($date->day && $date->month && $date->year) {
            $type = ContactImportantDate::TYPE_FULL_DATE;
        }

        // case: only know the year.
        if (! $date->day && ! $date->month && $date->year) {
            $type = ContactImportantDate::TYPE_YEAR;
        }

        // case: only know the month and day. In this case, we can't calculate
        //the age at all
        if ($date->day && $date->month && ! $date->year) {
            $type = ContactImportantDate::TYPE_MONTH_DAY;
        }

        return $type;
    }
}
