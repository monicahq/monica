<?php

namespace App\Services\Instance\Geolocalization;

use Illuminate\Support\Str;
use App\Models\Account\Place;
use App\Services\BaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use App\Exceptions\RateLimitedSecondException;
use App\Exceptions\MissingEnvVariableException;

class GetGPSCoordinate extends BaseService
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
     * Get the latitude and longitude from a place.
     * This method uses LocationIQ to process the geocoding.
     *
     * @param  array  $data
     * @return Place|null
     */
    public function execute(array $data)
    {
        $this->validateWeatherEnvVariables();

        $this->validate($data);

        $place = Place::where('account_id', $data['account_id'])
            ->findOrFail($data['place_id']);

        return $this->query($place);
    }

    /**
     * Make sure that geolocation env variables are set.
     *
     * @return void
     */
    private function validateWeatherEnvVariables()
    {
        if (! config('monica.enable_geolocation') || is_null(config('monica.location_iq_api_key'))) {
            throw new MissingEnvVariableException();
        }
    }

    /**
     * Build the query to send with the API call.
     *
     * @param  Place  $place
     * @return string|null
     */
    private function buildQuery(Place $place): ?string
    {
        if (($q = $place->getAddressAsString()) === null) {
            return null;
        }

        $query = http_build_query([
            'format' => 'json',
            'key' => config('monica.location_iq_api_key'),
            'q' => $q,
        ]);

        return Str::finish(config('location.location_iq_url'), '/').'search.php?'.$query;
    }

    /**
     * Actually make the call to the reverse geocoding API.
     *
     * @param  Place  $place
     * @return Place|null
     */
    private function query(Place $place): ?Place
    {
        if (($query = $this->buildQuery($place)) === null) {
            return null;
        }

        try {
            $response = Http::get($query);
            $response->throw();

            $place->latitude = $response->json('0.lat');
            $place->longitude = $response->json('0.lon');
            $place->save();

            return $place;
        } catch (RequestException $e) {
            if ($e->response->status() === 429 && ($error = $e->response->json('error')) && $error === 'Rate Limited Second') {
                throw new RateLimitedSecondException($e);
            } elseif ($e->response->status() !== 404 && $e->response->status() !== 400) {
                Log::error(__CLASS__.' '.__FUNCTION__.': Error making the call: '.$e->getMessage(), [
                    'query' => Str::of($query)->replace(config('monica.location_iq_api_key'), '******'),
                    $e,
                ]);
            }
        }

        return null;
    }
}
