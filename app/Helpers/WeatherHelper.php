<?php

namespace App\Helpers;

use App\Jobs\GetGPSCoordinate;
use App\Models\Account\Weather;
use App\Models\Contact\Address;
use App\Jobs\GetWeatherInformation;
use Illuminate\Support\Facades\Bus;

class WeatherHelper
{
    /**
     * Get the weather for the given address, if it exists.
     *
     * @param  Address|null  $address
     * @return Weather|null
     */
    public static function getWeatherForAddress($address): ?Weather
    {
        if (is_null($address)) {
            return null;
        }

        $weather = $address->place->weathers()
            ->orderBy('created_at', 'desc')
            ->first();

        // only get weather data if weather is either not existant or if is
        // more than 6h old
        if (is_null($weather) || ! $weather->created_at->between(now()->subHours(6), now())) {
            self::callWeatherAPI($address);
        }

        return $weather;
    }

    /**
     * Make the call to the weather service.
     *
     * @param  Address  $address
     */
    private static function callWeatherAPI(Address $address): void
    {
        $jobs = [];

        if (is_null($address->place->latitude)
            && config('monica.enable_geolocation') && ! is_null(config('monica.location_iq_api_key'))) {
            $jobs[] = new GetGPSCoordinate($address->place);
        }

        if (config('monica.enable_weather') && ! is_null(config('monica.weatherapi_key'))) {
            $jobs[] = new GetWeatherInformation($address->place);
        }

        Bus::batch($jobs)
            ->dispatch();
    }
}
