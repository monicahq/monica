<?php

namespace App\Services\Instance\Geolocalization;

use Illuminate\Support\Str;
use App\Models\Account\Place;
use App\Services\BaseService;
use function Safe\json_decode;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class GetGPSCoordinate extends BaseService
{
    protected $client;

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
     * @param array $data
     * @param GuzzleClient $client the Guzzle client, only needed when unit testing
     * @return Place|null
     */
    public function execute(array $data, GuzzleClient $client = null)
    {
        $this->validate($data);

        if (! is_null($client)) {
            $this->client = $client;
        } else {
            $this->client = new GuzzleClient();
        }

        $place = Place::where('account_id', $data['account_id'])
            ->findOrFail($data['place_id']);

        return $this->query($place);
    }

    /**
     * Build the query to send with the API call.
     *
     * @param Place $place
     * @return string|null
     */
    private function buildQuery(Place $place)
    {
        if (! config('monica.enable_geolocation')) {
            return;
        }

        if (is_null(config('monica.location_iq_api_key'))) {
            return;
        }

        $query = http_build_query([
            'format' => 'json',
            'key' => config('monica.location_iq_api_key'),
            'q' => $place->getAddressAsString(),
        ]);

        return Str::finish(config('location.location_iq_url'), '/').'search.php?'.$query;
    }

    /**
     * Actually make the call to the reverse geocoding API.
     *
     * @param Place $place
     * @return Place|null
     */
    private function query(Place $place)
    {
        $query = $this->buildQuery($place);

        if (is_null($query)) {
            return;
        }

        try {
            $response = $this->client->request('GET', $query);
        } catch (ClientException $e) {
            Log::error('Error making the call: '.$e);

            return;
        }

        $response = json_decode($response->getBody());

        $place->latitude = $response[0]->lat;
        $place->longitude = $response[0]->lon;
        $place->save();

        return $place;
    }
}
