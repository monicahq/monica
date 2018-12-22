<?php

namespace App\Services\Instance\Weather;

use App\Services\BaseService;
use App\Models\Account\Weather;
use App\Models\Account\Place;
use App\Exceptions\MissingEnvVariableException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use App\Services\Instance\Geolocalization\GetGPSCoordinate;

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
     * @throws App\Exceptions\MissingParameterException if the array that is given in parameter is not valid
     * @throws App\Exceptions\MissingEnvVariableException if the weather services are not enabled
     * @throws Illuminate\Database\Eloquent\ModelNotFoundException if the Place object is not found
     * @throws GuzzleHttp\Exception\ClientException if the request to Darksky crashed
     */
    public function execute(array $data)
    {
        $this->validateWeatherEnvVariables();

        $this->validate($data);

        $place = Place::findOrFail($data['place_id']);

        if (is_null($place->latitude)) {
            $place = $this->fetchGPS($place);

            if (is_null($place)) {
                return;
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
     * @return Weather
     * @throws Exception
     */
    private function query(Place $place) : Weather
    {
        $query = $this->buildQuery($place);

        $client = new GuzzleClient();
        $response = $client->request('GET', $query);
        $response = json_decode($response->getBody());

        $weather = new Weather();
        $weather->weather_json = $response;
        $weather->account_id = $place->account_id;
        $weather->place_id = $place->id;
        $weather->save();

        return $weather;
    }

    /**
     * Prepare the query that will be send to Darksky.
     *
     * @param Place $place
     * @return string
     */
    private function buildQuery(Place $place)
    {
        $query = 'https://api.darksky.net/forecast/';
        $query .= config('monica.darksky_api_key');
        $query .= '/';
        $query .= $place->latitude.','.$place->longitude;
        $query .= '?exclude=alerts,minutely,hourly,daily,flags';

        return $query;
    }

    /**
     * Fetch missing longitude/latitude.
     *
     * @param Place $place
     * @return Place|null
     */
    private function fetchGPS(Place $place)
    {
        return (new GetGPSCoordinate)->execute([
            'account_id' => $place->account_id,
            'place_id' => $place->id,
        ]);
    }
}
