<?php

namespace App\Services\Weather;

use App\Models\Account\Weather;
use App\Services\BaseService;
use Illuminate\Validation\Rule;
use Naughtonium\LaravelDarkSky\Facades\DarkSky;
use Naughtonium\LaravelDarkSky\LaravelDarkSkyServiceProvider;

class GetWeatherInformation extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'latitude' => 'required|string',
            'longitude' => 'required|string',
        ];
    }

    /**
     * Get the weather information.
     *
     * @param array $data
     * @return Weather
     */
    public function execute(array $data) : Weather
    {
        $this->validate($data);

        $weather = new Weather();

        $weather->weather_json = DarkSky::location($data['latitude'], $data['longitude'])
                    ->excludes(['alerts', 'minutely', 'hourly', 'daily', 'flags'])
                    ->get();

        $weather->save();

        return $weather;
    }
}
