<?php

namespace App\Services\Instance\Weather;

use Illuminate\Support\Str;
use App\Models\Account\Place;
use App\Services\BaseService;
use App\Jobs\GetGPSCoordinate;
use App\Models\Account\Weather;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Exceptions\MissingEnvVariableException;
use Illuminate\Http\Client\HttpClientException;

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
     * @return Weather|null
     * @throws \Illuminate\Validation\ValidationException if the array that is given in parameter is not valid
     * @throws \App\Exceptions\MissingEnvVariableException if the weather services are not enabled
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the Place object is not found
     */
    public function execute(array $data): ?Weather
    {
        $this->validateWeatherEnvVariables();

        $this->validate($data);

        $place = Place::findOrFail($data['place_id']);

        if (is_null($place->latitude)) {
            $place = $this->fetchGPS($place);

            if (is_null($place)) {
                return null;
            }
        }

        return $this->query($place);
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

        if (is_null(config('monica.darksky_api_key'))) {
            throw new MissingEnvVariableException();
        }
    }

    /**
     * Actually make the call to Darksky.
     *
     * @param Place $place
     * @return Weather|null
     * @throws \Exception
     */
    private function query(Place $place): ?Weather
    {
        $query = $this->buildQuery($place);

        try {
            $response = Http::get($query);
            $response->throw();

            $weather = new Weather();
            $weather->weather_json = $response->object();
            $weather->account_id = $place->account_id;
            $weather->place_id = $place->id;
            $weather->save();

            return $weather;
        } catch (HttpClientException $e) {
            Log::error('Error making the call: '.$e);
        }

        return null;
    }

    /**
     * Prepare the query that will be send to Darksky.
     *
     * @param Place $place
     * @return string
     */
    private function buildQuery(Place $place)
    {
        $url = Str::finish(config('location.darksky_url'), '/');
        $key = config('monica.darksky_api_key');
        $coords = $place->latitude.','.$place->longitude;

        $query = http_build_query([
            'exclude' => 'alerts,minutely,hourly,daily,flags',
            'units' => 'si',
        ]);

        return $url.$key.'/'.$coords.'?'.$query;
    }

    /**
     * Fetch missing longitude/latitude.
     *
     * @param Place $place
     * @return Place|null
     */
    private function fetchGPS(Place $place): ?Place
    {
        if (config('monica.enable_geolocation') && ! is_null(config('monica.location_iq_api_key'))) {
            GetGPSCoordinate::dispatchSync($place);
            $place->refresh();

            return $place;
        }

        return null;
    }
}
