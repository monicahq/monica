<?php

namespace App\Services\Weather;

use App\Services\BaseService;
use App\Models\Account\Weather;
use Naughtonium\LaravelDarkSky\Facades\DarkSky;

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
