<?php

namespace App\Services\Instance\Weather;

use Illuminate\Support\Str;
use App\Models\Account\Place;
use App\Services\BaseService;
use App\Models\Account\Weather;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Exceptions\NoCoordinatesException;
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
            'account_id' => 'required|integer|exists:accounts,id',
            'place_id' => 'required|integer|exists:places,id',
        ];
    }

    /**
     * Get the weather information.
     *
     * @param  array  $data
     * @return Weather|null
     *
     * @throws \Illuminate\Validation\ValidationException if the array that is given in parameter is not valid
     * @throws \App\Exceptions\MissingEnvVariableException if the weather services are not enabled
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the Place object is not found
     */
    public function execute(array $data): ?Weather
    {
        $this->validateWeatherEnvVariables();

        $this->validate($data);

        $place = Place::where('account_id', $data['account_id'])
            ->findOrFail($data['place_id']);

        if (is_null($place->latitude)) {
            throw new NoCoordinatesException();
        }

        return $this->query($place, 'en');
    }

    /**
     * Make sure that weather env variables are set.
     *
     * @return void
     */
    private function validateWeatherEnvVariables()
    {
        if (! config('monica.enable_weather') || is_null(config('monica.weatherapi_key'))) {
            throw new MissingEnvVariableException();
        }
    }

    /**
     * Actually make the call to Darksky.
     *
     * @param  Place  $place
     * @return Weather|null
     *
     * @throws \Exception
     */
    private function query(Place $place, ?string $lang = null): ?Weather
    {
        $query = $this->buildQuery($place, $lang);

        try {
            $response = Http::get($query);
            $response->throw();

            return Weather::create([
                'account_id' => $place->account_id,
                'place_id' => $place->id,
                'weather_json' => $response->object(),
            ]);
        } catch (HttpClientException $e) {
            Log::error(__CLASS__.' '.__FUNCTION__.': Error making the call: '.$e->getMessage(), [
                'query' => Str::of($query)->replace(config('monica.weatherapi_key'), '******'),
                $e,
            ]);
        }

        return null;
    }

    /**
     * Prepare the query that will be send to Darksky.
     *
     * @param  Place  $place
     * @return string
     */
    private function buildQuery(Place $place, ?string $lang = null)
    {
        $coords = $place->latitude.','.$place->longitude;

        $query = [
            'key' => config('monica.weatherapi_key'),
            'q' => $coords,
            'lang' => $lang ?? 'en',
        ];
        if ($lang !== null && $lang !== 'en') {
            $query['lang'] = $lang;
        }

        return Str::of(config('location.weatherapi_url'))->rtrim('/').'?'.http_build_query($query);
    }
}
