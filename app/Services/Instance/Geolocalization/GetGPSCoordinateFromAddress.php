<?php

namespace App\Services\Instance\Geolocalization;

use App\Services\BaseService;
use App\Models\Contact\Address;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class GetGPSCoordinateFromAddress extends BaseService
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
            'address_id' => 'required|integer|exists:addresses,id',
        ];
    }

    /**
     * Get the latitude and longitude from an address.
     * This method uses LocationIQ to process the geocoding.
     *
     * @param array $data
     * @return Address|null
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $address = Address::where('account_id', $data['account_id'])
            ->findOrFail($data['address_id']);

        return $this->query($address);
    }

    /**
     * Build the query to send with the API call.
     *
     * @param Address $address
     * @return string|null
     */
    private function getQuery(Address $address)
    {
        if (! config('monica.enable_geolocation')) {
            return;
        }

        if (is_null(config('monica.location_iq_api_key'))) {
            return;
        }

        $query = 'https://us1.locationiq.com/v1/search.php?key=';
        $query .= config('monica.location_iq_api_key');
        $query .= '&q=';
        $query .= urlencode($address->getFullAddress());
        $query .= '&format=json';

        return $query;
    }

    /**
     * Actually make th call the reverse geocoding API.
     *
     * @param Address $address
     * @return Address|null
     */
    private function query(Address $address)
    {
        $query = $this->getQuery($address);

        if (is_null($query)) {
            return;
        }

        $client = new GuzzleClient();

        try {
            $response = $client->request('GET', $query);
        } catch (ClientException $e) {
            return;
        }

        $response = json_decode($response->getBody());

        $address->latitude = $response[0]->lat;
        $address->longitude = $response[0]->lon;
        $address->save();

        return $address;
    }
}
