<?php

namespace App\Helpers;

use Auth;
use Carbon\Carbon;
use Jenssegers\Date\Date;

class DateHelper
{
    /**
     * Creates a Carbon object.
     *
     * @param string date
     * @param string timezone
     * @return Carbon
     */
    public static function createDateFromFormat($date, $timezone)
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, $timezone);

        return $date;
    }

    /**
     * Return a date according to the timezone of the user, in a short format
     * like "Oct 29, 1981".
     *
     * @param Carbon $date
     * @return string
     */
    public static function getShortDate($date, $locale = null)
    {
        $date = new Date($date);
        $locale = self::getLocale($locale);

        switch ($locale) {
            case 'en':
                $format = 'M d, Y';
                break;
            case 'pt-br':
            case 'fr':
                $format = 'd M Y';
                break;
            default:
                $format = 'M d, Y';
                break;
        }

        return $date->format($format);
    }

    /**
     * Return a date and the time according to the timezone of the user, in a short format
     * like "Oct 29, 1981 19:32".
     *
     * @param Carbon $date
     * @return string
     */
    public static function getShortDateWithTime($date, $locale = null)
    {
        $date = new Date($date);
        $locale = self::getLocale($locale);

        switch ($locale) {
            case 'en':
                $format = 'M d, Y H:i';
                break;
            case 'pt-br':
            case 'fr':
                $format = 'd M Y H:i';
                break;
            default:
                $format = 'M d, Y H:i';
        }

        return $date->format($format);
    }

    /**
     * Returns the locale of the instance, if defined. English by default.
     *
     * @param string
     * @return string
     */
    public static function getLocale($locale = null)
    {
        if (Auth::check()) {
            $locale = $locale ?: Auth::user()->locale;
        } else {
            $locale = $locale ?: 'en';
        }

        Date::setLocale($locale);

        return $locale;
    }

    /**
     * Calculate the next date an event will occur.
     * @param Carbon
     * @param string
     * @param int
     * @return Carbon
     */
    public static function calculateNextOccuringDate($date, $frequency_type, $frequency_number)
    {
        if ($frequency_type == 'week') {
            $date->addWeeks($frequency_number);
        }
        if ($frequency_type == 'month') {
            $date->addMonths($frequency_number);
        }
        if ($frequency_type == 'year') {
            $date->addYears($frequency_number);
        }

        return $date;
    }

    /**
     * Calculate the age given a date and the fact that the date is an
     * approximation or not.
     *
     * @param  Carbon $date          [description]
     * @param  string $approximation 'true' or 'false'
     * @return string
     */
    public static function getAgeAsString($date, $approximation)
    {
        Carbon::setLocale(Auth::user()->locale);
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, Auth::user()->timezone);
        $age = $date->diffInYears(Carbon::now());

        if ($age < 1) {
            $ageInMonths = self::createDateFromFormat($date, Auth::user()->timezone)->diffInMonths(Carbon::now());

            if ($approximation == 'true') {
                // returns Approx. 3 months old
                return trans('people.age_approximate_in_months', ['age' => $ageInMonths]);
            } else {
                // returns 3 month old (Oct 29, 1981)
                $html = '<span class="age-date">('.self::getShortDate($date, Auth::user()->locale).')</span>';

                return trans('people.age_exact_in_months', ['age' => $ageInMonths, 'html' => $html]);
            }
        }

        if ($approximation == 'true') {
            // returns Approx. 3 years old
            return trans('people.age_approximate_in_years', ['age' => $age]);
        } else {
            // returns 23 years old (Oct 29, 1981)
            $html = '<span class="age-date">('.self::getShortDate($date, Auth::user()->locale).')</span>';

            return trans('people.age_exact_in_years', ['age' => $age, 'html' => $html]);
        }
    }
}
