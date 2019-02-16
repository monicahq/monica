<?php

namespace App\Helpers;

use Vectorface\Whip\Whip;
use OK\Ipstack\Client as Ipstack;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;

class RequestHelper
{
    /**
     * Get client ip.
     *
     * @return array|string
     */
    public static function ip()
    {
        $whip = new Whip();
        $ip = $whip->getValidIpAddress();
        if ($ip === false) {
            $ip = Request::header('Cf-Connecting-Ip');
            if (is_null($ip)) {
                $ip = Request::ip();
            }
        }

        return $ip;
    }

    /**
     * Get client country.
     *
     * @return string|null
     */
    public static function country()
    {
        $position = Location::get();

        if (! $position) {
            return;
        }

        return $position->countryCode;
    }

    /**
     * Get client country and currency.
     *
     * @param string $ip
     * @return array
     */
    public static function infos($ip)
    {
        if (config('location.ipstack_apikey') != null) {
            $ipstack = new Ipstack(config('location.ipstack_apikey'));
            $position = $ipstack->get($ip ?? static::ip(), true);

            if (! is_null($position) && array_get($position, 'country_code', null)) {
                return [
                    'country' => array_get($position, 'country_code', null),
                    'currency' => array_get($position, 'currency.code', null),
                    'timezone' => array_get($position, 'time_zone.id', null),
                ];
            }
        }

        return [
            'country' => static::country(),
            'currency' => null,
            'timezone' => null,
        ];
    }
}
