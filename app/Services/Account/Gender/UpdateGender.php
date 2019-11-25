<?php

namespace App\Services\Account\Gender;

use App\Services\BaseService;
use App\Models\Contact\Gender;
use Illuminate\Validation\Rule;

class UpdateGender extends BaseService
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
            'gender_id' => 'required|integer|exists:genders,id',
            'name' => 'required|string|max:255',
            'type' => [
                'required',
                Rule::in([Gender::MALE, Gender::FEMALE, Gender::OTHER]),
            ],
        ];
    }

    /**
     * Update a gender.
     *
     * @param array $data
     * @return Gender
     */
    public function execute(array $data) : Gender
    {
        $this->validate($data);

        $gender = Gender::where('account_id', $data['account_id'])
            ->findOrFail($data['gender_id']);

        $gender->update([
            'name' => $data['name'],
            'type' => $data['type'],
        ]);

        return $gender;
    }
}
