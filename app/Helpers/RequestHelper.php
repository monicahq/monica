<?php

namespace App\Helpers;

use Vectorface\Whip\Whip;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;

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
        if ($ip === false)
        {
            $ip = Request::header('Cf-Connecting-Ip');
            if (is_null($ip))
            {
                $ip = Request::ip();
            }
        }
        return $ip;
    }

    /**
     * Get client country.
     *
     * @return string
     */
    public static function country()
    {
        $position = Location::get();
        if (is_null($position))
        {
            return;
        }
        return $position->countryCode;
    }
}
