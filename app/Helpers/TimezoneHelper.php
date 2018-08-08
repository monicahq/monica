<?php

namespace App\Helpers;

use DateTimeZone;

class TimezoneHelper
{
    /**
     * Get a list of all timezones
     * 
     * @return array
     */
    public static function listTimezones() : array
    {
        $list = [];
        $timezones = DateTimeZone::listIdentifiers();

        foreach ($timezones as $timezone) {
            list($tz, $name) = self::formatTimezone($timezone);
            array_push($list, [
                'id' => $tz,
                'timezone' => $timezone,
                'name' => $name
            ]);
        }

        return array_values(array_sort($list, function ($value) {
            return $value['id'];
        }));
    } 

    private static function formatTimezone($timezone) : array
    {
        $time = now($timezone);

        $offset = $time->format('P');

        if ($timezone == 'UTC') {
            $formatted = '(UTC) Universal Time Coordinated';
        } else {
            $i = strstr($time->tzName, '/');
            if ($i > 0) {
                $timezone = substr(strstr($time->tzName, '/'), 1);
            }
            $timezone = str_replace('St_', 'St. ', $timezone);
            $timezone = str_replace('_', ' ', $timezone);

            $formatted = '(UTC ' . $offset . ') ' . $timezone;
        }

        $tz = str_replace(':', '', $offset);
        $tz = intval($tz);

        return [$tz, $formatted];
    }

    /**
     * Render listbox of timezones.
     *
     * @param string $name
     * @param string $selected
     * @param mixed $attr
     * @return string
     **/
    public static function list($name, $selected='', $attr='') : string
    {
        // Attributes for select element
        $attrSet = null;
        if (!empty($attr)) {
            if (is_array($attr)) {
                foreach ($attr as $attr_name => $attr_value) {
                    $attrSet .= ' ' .$attr_name. '="' .$attr_value. '"';
                }
            } else {
                $attrSet = ' ' .$attr;
            }
        }

        $listbox = '<select name="' .$name. '"' .$attrSet. '>';

        $list = self::listTimezones();

        foreach ($list as $key => $timezone) {
            $selected_attr = ($selected == $timezone['timezone']) ? ' selected="selected"' : '';

            $listbox .= '<option value="' .$timezone['timezone']. '"' .$selected_attr. '>';
            $listbox .= $timezone['name'];
            $listbox .= '</option>';
        }

        $listbox .= '</select>';

        return $listbox;
    }
}
