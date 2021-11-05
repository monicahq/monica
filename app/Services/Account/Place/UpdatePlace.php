<?php

namespace App\Services\Account\Place;

use App\Models\Account\Place;
use App\Services\BaseService;
use App\Jobs\GetGPSCoordinate;

class UpdatePlace extends BaseService
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
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:3',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }

    /**
     * Update a place.
     *
     * @param  array  $data
     * @return Place
     */
    public function execute(array $data): Place
    {
        $this->validate($data);

        /** @var Place */
        $place = Place::where('account_id', $data['account_id'])
            ->findOrFail($data['place_id']);

        $place->update([
            'street' => $this->nullOrValue($data, 'street'),
            'city' => $this->nullOrValue($data, 'city'),
            'province' => $this->nullOrValue($data, 'province'),
            'postal_code' => $this->nullOrValue($data, 'postal_code'),
            'country' => $this->nullOrValue($data, 'country'),
            'latitude' => $this->nullOrValue($data, 'latitude'),
            'longitude' => $this->nullOrValue($data, 'longitude'),
        ]);

        if (is_null($place->latitude) || is_null($place->longitude)) {
            $this->getGeocodingInfo($place);
        }

        return $place;
    }

    /**
     * Get geocoding information about the place (lat/longitude).
     *
     * @param  Place  $place
     * @return void
     */
    private function getGeocodingInfo(Place $place)
    {
        if (config('monica.enable_geolocation') && ! is_null(config('monica.location_iq_api_key'))) {
            GetGPSCoordinate::dispatch($place);
        }
    }
}
