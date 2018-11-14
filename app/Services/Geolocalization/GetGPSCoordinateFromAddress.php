<?php

namespace App\Services\Geolocalization;

use App\Services\BaseService;

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
            'street' => 'string',
            'city' => 'required|string',
            'province' => 'string',
            'postal_code' => 'string',
            'country' => 'string',
        ];
    }

    /**
     * Get the latitude and longitude from an address.
     * This method uses LocationIQ to process the geocoding.
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data) : array
    {
        $this->validate($data);

        $weather->save();

        return $weather;
    }
}
