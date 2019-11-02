<?php

namespace App\Services\Contact\Address;

use App\Models\Account\Place;
use App\Services\BaseService;
use App\Models\Contact\Address;
use App\Services\Account\Place\UpdatePlace;

class UpdateAddress extends BaseService
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
            'contact_id' => 'required|integer|exists:contacts,id',
            'address_id' => 'required|integer|exists:addresses,id',
            'name' => 'nullable|string|max:255',
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
     * Update an address.
     *
     * @param array $data
     * @return Address
     */
    public function execute(array $data) : Address
    {
        $this->validate($data);

        $address = Address::where('account_id', $data['account_id'])
            ->where('contact_id', $data['contact_id'])
            ->findOrFail($data['address_id']);

        $this->updatePlace($data, $address);

        $address->update([
            'name' => $this->nullOrValue($data, 'name'),
        ]);

        return $address;
    }

    /**
     * Create a place for the given address.
     *
     * @param array $data
     * @param Address $address
     * @return Place
     */
    private function updatePlace(array $data, Address $address)
    {
        $request = [
            'account_id' => $data['account_id'],
            'place_id' => $address->place_id,
            'street' => $this->nullOrValue($data, 'street'),
            'city' => $this->nullOrValue($data, 'city'),
            'province' => $this->nullOrValue($data, 'province'),
            'postal_code' => $this->nullOrValue($data, 'postal_code'),
            'country' => $this->nullOrValue($data, 'country'),
            'latitude' => $this->nullOrValue($data, 'latitude'),
            'longitude' => $this->nullOrValue($data, 'longitude'),
        ];

        return app(UpdatePlace::class)->execute($request);
    }
}
