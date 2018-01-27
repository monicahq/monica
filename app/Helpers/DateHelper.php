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
     * Return the month of the date according to the timezone of the user
     * like "Oct", or "Dec.
     *
     * @param Carbon $date
     * @return string
     */
    public static function getShortMonth($date, $locale = null)
    {
        $date = new Date($date);
        $locale = self::getLocale($locale);
        $format = 'M';

        return $date->format($format);
    }

    /**
     * Return the day of the date according to the timezone of the user
     * like "Mon", or "Wed.
     *
     * @param Carbon $date
     * @return string
     */
    public static function getShortDay($date, $locale = null)
    {
        $date = new Date($date);
        $locale = self::getLocale($locale);
        $format = 'D';

        return $date->format($format);
    }

    /**
     * Return a date according to the timezone of the user, in a short format
     * like "Oct 29".
     *
     * @param Carbon $date
     * @return string
     */
    public static function getShortDateWithoutYear($date, $locale = null)
    {
        $date = new Date($date);
        $locale = self::getLocale($locale);

        switch ($locale) {
            case 'en':
                $format = 'M d';
                break;
            case 'pt-br':
            case 'fr':
                $format = 'd M';
                break;
            default:
                $format = 'M d';
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
     * Add a given number of week/month/year to a date.
     * @param Carbon $date      the start date
     * @param string $frequency week/month/year
     * @param int $number    the number of week/month/year to increment to
     */
    public static function addTimeAccordingToFrequencyType(Carbon $date, $frequency, $number)
    {
        switch ($frequency) {
            case 'week':
                $date->addWeeks($number);
                break;
            case 'month':
                $date->addMonths($number);
                break;
            default:
                $date->addYears($number);
                break;
        }

        return $date;
    }

    /**
     * Get the name of the month and year of a given date with a given number
     * of months more.
     * @param  int    $month
     * @return string
     */
    public static function getMonthAndYear(int $month)
    {
        $month = Carbon::now()->addMonthsNoOverflow($month)->format('M');
        $year = Carbon::now()->addMonthsNoOverflow($month)->format('Y');

        return $month.' '.$year;
    }

    /**
     * Gets the next theoritical billing date.
     * This is used on the Upgrade page to tell the user when the next billing
     * date would be if he subscribed.
     *
     * @param  string
     * @return Carbon
     */
    public static function getNextTheoriticalBillingDate(String $interval)
    {
        if ($interval == 'monthly') {
            return Carbon::now()->addMonth();
        }

        return Carbon::now()->addYear();
    }
}
