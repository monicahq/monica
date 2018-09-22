<?php

namespace App\Helpers;

use Vectorface\Whip\Whip;
use OK\Ipstack\Client as Ipstack;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class RequestHelper
{
    /**
     * Get client ip.
     *
     * @return string
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
     * Get client country and currency.
     *
     * @param string $ip
     * @return Collection
     */
    public static function infos($ip): Collection
    {
        if (config('location.ipstack_apikey') === null) {
            return null;
        }

        $ipstack = new Ipstack(config('location.ipstack_apikey'));
        $position = $ipstack->get($ip ?? static::ip());

        if (is_null($position)) {
            return null;
        }

        return collect([
            'country' => array_get($position, 'country_code'),
            'currency' => array_get($position, 'currency.code'),
            'timezone' => array_get($position, 'time_zone.id'),
        ]);
    }
}
