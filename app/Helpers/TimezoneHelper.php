<?php

namespace App\Helpers;

use DateTimeZone;

class TimezoneHelper
{
    /**
     * Get a list of all timezones.
     *
     * @return array
     */
    public static function getListOfTimezones() : array
    {
        $list = [];
        $timezones = DateTimeZone::listIdentifiers();

        foreach ($timezones as $timezone) {
            list($tz, $name) = self::formatTimezone($timezone);
            array_push($list, [
                'id' => $tz,
                'timezone' => $timezone,
                'name' => $name,
            ]);
        }

        $collect = collect($list)
            ->groupBy('id')
            ->sortKeys();

        $result = [];
        foreach ($collect as $item) {
            $values = array_values(array_sort($item, function ($value) {
                return $value['name'];
            }));
            foreach ($values as $val) {
                array_push($result, $val);
            }
        }

        return $result;
    }

    private static function formatTimezone($timezone) : array
    {
        $dtimezone = new DateTimeZone($timezone); 
        $time = now($timezone);

        $offset = $time->format('P');

        $loc = $dtimezone->getLocation();

        if ($timezone == 'UTC') {
            $formatted = '(UTC) Universal Time Coordinated';
        } else {
            $name = $time->tzName;
            $i = strpos($name, '/');
            if ($i > 0) {
                $name = substr($name, $i + 1);
            }
            $name = str_replace(['St_', '/', '_'], ['St. ', ', ', ' '], $name);

            if (empty($loc['comments'])) {
                $formatted = '(UTC '.$offset.') '.$name;
            } else {
                $formatted = '(UTC '.$offset.') '.$loc['comments'].' ('.$name.')';
            }
        }


        $tz = str_replace(':', '', $offset);
        $tz = intval($tz);

        return [$tz, $formatted];
    }

    /**
     * Adjust a timezone with equivalent name (remove deprecated).
     *
     * @param string $timezone
     * @return string
     */
    public static function adjustEquivalentTimezone($timezone) : string
    {
        // See https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
        switch ($timezone) {
            case 'US/Central':
                return 'America/Chicago';
            case 'US/Arizona':
                return 'America/Phoenix';
            case 'Asia/Calcutta':
                return 'Asia/Kolkata';
            case 'US/Eastern':
                return 'America/New_York';
            case 'Etc/Greenwich':
                // This is not an equivalent, but it the same zone
                return 'UTC';
            case 'US/Mountain':
                return 'America/Denver';
            case 'US/Alaska':
                return 'America/Anchorage';
            case 'Canada/Atlantic':
                return 'America/Halifax';
            case 'US/East-Indiana':
                return 'America/Indiana/Indianapolis';
            case 'Asia/Katmandu':
                return 'Asia/Kathmandu';
            case 'Canada/Saskatchewan':
                return 'America/Regina';
            case 'Australia/Canberra':
                return 'Australia/Sydney';
            case 'Asia/Ulan_Bator':
                return 'Asia/Ulaanbaatar';
            case 'Canada/Newfoundland':
                return 'America/St_Johns';
            case 'Asia/Chongqing':
                return 'Asia/Shanghai';
            case 'Pacific/Samoa':
                return 'Pacific/Pago_Pago';
            case 'Asia/Rangoon':
                return 'Asia/Yangon';
            default:
                return $timezone;
        }
    }
}
