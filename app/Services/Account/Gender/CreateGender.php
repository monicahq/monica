<?php

namespace App\Services\Account\Gender;

use App\Services\BaseService;
use App\Models\Contact\Gender;

class CreateGender extends BaseService
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
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Create a gender.
     *
     * @param array $data
     * @return Gender
     */
    public function execute(array $data) : Gender
    {
        $this->validate($data);

        return Gender::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);
    }
}
