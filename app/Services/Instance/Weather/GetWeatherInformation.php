<?php

namespace App\Services\Instance\Weather;

use App\Services\BaseService;
use App\Models\Account\Weather;
use App\Exceptions\MissingEnvVariableException;
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
            'place_id' => 'required|integer|exists:places,id',
        ];
    }

    /**
     * Get the weather information.
     *
     * @param array $data
     * @return Weather
     * @throws App\Exceptions\MissingParameterException if the array that is given in parameter is not valid
     * @throws App\Exceptions\MissingEnvVariableException if the weather services are not enabled
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException if the Place object is not found
     */
    public function execute(array $data) : Weather
    {
        $this->validateWeatherEnvVariables();

        $this->validate($data);

        $place = Place::findOrFail($data['place_id']);

        if (is_null($place->latitude)) {
            return;
        }

        // is the weather service enable in the instance
        // is darskhy api key provided
        // does the place have long/lat
        // can we fetch long/lat with the service (from ENV file)?
        // fetch long/lat
        // then request weather

        $weather = new Weather();

        $weather->weather_json = DarkSky::location($place->latitude, $place->longitude)
                    ->excludes(['alerts', 'minutely', 'hourly', 'daily', 'flags'])
                    ->get();

        $weather->save();

        return $weather;
    }

    /**
     * Make sure that weather env variables are set.
     *
     * @return void
     */
    private function validateWeatherEnvVariables()
    {
        if (! config('monica.enable_weather')) {
            throw new MissingEnvVariableException();
        }

        if (is_null(config('monica.enable_weather'))) {
            throw new MissingEnvVariableException();
        }
    }
}
