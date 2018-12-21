<?php

namespace App\Services\Contact\Address;

use App\Models\Account\Place;
use App\Services\BaseService;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Services\Account\Place\CreatePlace;

class CreateAddress extends BaseService
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
     * Create an address.
     *
     * @param array $data
     * @return Address
     */
    public function execute(array $data) : Address
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        $place = $this->createPlace($data);

        return Address::create([
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'place_id' => $place->id,
            'name' => $this->nullOrValue($data, 'name'),
        ]);
    }

    /**
     * Create a place for the given address.
     *
     * @param array $data
     * @return Place
     */
    private function createPlace(array $data)
    {
        $request = [
            'account_id' => $data['account_id'],
            'street' => $this->nullOrValue($data, 'street'),
            'city' => $this->nullOrValue($data, 'city'),
            'province' => $this->nullOrValue($data, 'province'),
            'postal_code' => $this->nullOrValue($data, 'postal_code'),
            'country' => $this->nullOrValue($data, 'country'),
            'latitude' => $this->nullOrValue($data, 'latitude'),
            'longitude' => $this->nullOrValue($data, 'longitude'),
        ];

        return (new CreatePlace)->execute($request);
    }
}
