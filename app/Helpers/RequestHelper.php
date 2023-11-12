<?php

namespace App\Helpers;

use Stevebauman\Location\Facades\Location;

class RequestHelper
{
    /**
     * Get client country and currency.
     */
    public static function infos(string $ip): array
    {
        $position = Location::get($ip);

        return [
            'country' => optional($position)->countryCode,
            'currency' => optional($position)->currencyCode,
            'timezone' => optional($position)->timezone,
        ];
    }

    /**
     * Get client country.
     *
     * @param  string  $ip
     */
    public static function country($ip): ?string
    {
        $position = Location::get($ip);

        return optional($position)->countryCode;
    }
}
