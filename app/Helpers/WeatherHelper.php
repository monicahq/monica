<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\Account\Weather;
use App\Models\Contact\Address;
use App\Services\Instance\Weather\GetWeatherInformation;

class WeatherHelper
{
    /**
     * Get the weather for the given address, if it exists.
     *
     * @param Address|null $address
     * @return Weather|null
     */
    public static function getWeatherForAddress($address)
    {
        if (is_null($address)) {
            return;
        }

        $weather = $address->place->weathers()->orderBy('created_at', 'desc')->first();

        // only get weather data if weather is either not existant or if is
        // more than 6h old
        if (is_null($weather)) {
            $weather = self::callWeatherAPI($address);
        } else {
            if (! $weather->created_at->between(Carbon::now()->subHour(6), Carbon::now())) {
                $weather = self::callWeatherAPI($address);
            }
        }

        return $weather;
    }

    /**
     * Make the call to the weather service.
     *
     * @param Address $address
     * @return Weather|null
     */
    private static function callWeatherAPI(Address $address)
    {
        try {
            $weather = app(GetWeatherInformation::class)->execute([
                'place_id' => $address->place->id,
            ]);
        } catch (\Exception $e) {
            $weather = null;
        }

        return $weather;
    }
}
