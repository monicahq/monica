<?php

namespace App\Services\Account\Place;

use App\Models\Account\Place;
use App\Services\BaseService;

class DestroyPlace extends BaseService
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
     * Destroy a place.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

        $place = Place::where('account_id', $data['account_id'])
            ->findOrFail($data['place_id']);

        $place->delete();

        return true;
    }
}
