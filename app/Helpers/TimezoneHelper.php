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
    public static function getListOfTimezones(): array
    {
        $list = [];
        $timezones = DateTimeZone::listIdentifiers();

        if ($timezones !== false) {
            foreach ($timezones as $timezone) {
                [$tz, $name] = self::formatTimezone($timezone);
                array_push($list, [
                    'id' => $tz,
                    'timezone' => $timezone,
                    'name' => $name,
                ]);
            }
        }

        $collect = collect($list)
            ->groupBy('id')
            ->sortKeys();

        $result = [];
        foreach ($collect as $item) {
            $values = $item->sortByCollator(function ($value) {
                return $value['name'];
            });
            foreach ($values as $val) {
                array_push($result, $val);
            }
        }

        return $result;
    }

    /**
     * Format a timezone to be displayed (english only).
     *
     * @param  string  $timezone
     * @return array int value of the offset, string formatted timezone
     */
    private static function formatTimezone($timezone): array
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
     * Equivalent timezone to convert deprecated timezone.
     *
     * @var array<string>
     *
     * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
     */
    protected static $equivalentTimezone = [
        'Australia/Canberra' => 'Australia/Sydney',
        'Asia/Calcutta' => 'Asia/Kolkata',
        'Asia/Chongqing' => 'Asia/Shanghai',
        'Asia/Katmandu' => 'Asia/Kathmandu',
        'Asia/Rangoon' => 'Asia/Yangon',
        'Asia/Ulan_Bator' => 'Asia/Ulaanbaatar',
        'Canada/Atlantic' => 'America/Halifax',
        'Canada/Newfoundland' => 'America/St_Johns',
        'Canada/Saskatchewan' => 'America/Regina',
        'Etc/Greenwich' => 'UTC', // This is not an equivalent, but it the same zone
        'Pacific/Samoa' => 'Pacific/Pago_Pago',
        'US/Alaska' => 'America/Anchorage',
        'US/Arizona' => 'America/Phoenix',
        'US/Central' => 'America/Chicago',
        'US/East-Indiana' => 'America/Indiana/Indianapolis',
        'US/Eastern' => 'America/New_York',
        'US/Mountain' => 'America/Denver',
    ];

    /**
     * Adjust a timezone with equivalent name (remove deprecated).
     *
     * @param  string  $timezone
     * @return string
     */
    public static function adjustEquivalentTimezone($timezone): string
    {
        if (array_key_exists($timezone, self::$equivalentTimezone)) {
            return self::$equivalentTimezone[$timezone];
        }

        return $timezone;
    }
}
