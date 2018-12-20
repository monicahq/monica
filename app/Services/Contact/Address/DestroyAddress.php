<?php

namespace App\Services\Contact\Address;

use App\Services\BaseService;
use App\Models\Contact\Address;
use App\Services\Account\Place\DestroyPlace;

class DestroyAddress extends BaseService
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
        ];
    }

    /**
     * Destroy an address.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $address = Address::where('account_id', $data['account_id'])
            ->where('contact_id', $data['contact_id'])
            ->findOrFail($data['address_id']);

        (new DestroyPlace)->execute([
            'account_id' => $data['account_id'],
            'place_id' => $address->place_id,
        ]);

        $address->delete();

        return true;
    }
}
