<?php

namespace App\Helpers;

use Vectorface\Whip\Whip;
use Illuminate\Support\Arr;
use OK\Ipstack\Client as Ipstack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;

class RequestHelper
{
    /**
     * Get client ip.
     *
     * @return array|string|null
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
     * @param  string  $ip
     * @return string|null
     */
    public static function country($ip): ?string
    {
        $position = Location::get($ip);

        return $position ? $position->countryCode : null;
    }

    /**
     * Get client country and currency.
     *
     * @param  string|null  $ip
     * @return array
     */
    public static function infos($ip)
    {
        $ip = $ip ?? static::ip();

        if (config('location.ipstack_apikey') != null) {
            $ipstack = new Ipstack(config('location.ipstack_apikey'));
            $position = $ipstack->get($ip, true);

            if ($position !== null && Arr::get($position, 'country_code')) {
                return [
                    'country' => Arr::get($position, 'country_code'),
                    'currency' => Arr::get($position, 'currency.code'),
                    'timezone' => Arr::get($position, 'time_zone.id'),
                ];
            }
        }

        if (config('location.ipdata.token') != null) {
            try {
                $position = static::getIpData($ip);

                return [
                    'country' => Arr::get($position, 'country_code'),
                    'currency' => Arr::get($position, 'currency.code'),
                    'timezone' => Arr::get($position, 'time_zone.name'),
                ];
            } catch (\Exception $e) {
                // skip
            }
        }

        return [
            'country' => static::country($ip),
            'currency' => null,
            'timezone' => null,
        ];
    }

    /**
     * Get data from ipdata.
     *
     * @param  string  $ip
     * @return array
     */
    private static function getIpData(string $ip): array
    {
        $token = config('location.ipdata.token', '');

        $url = "https://api.ipdata.co/{$ip}?api-key=".$token;

        return Http::get($url)->throw()->json();
    }
}
