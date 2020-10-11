<?php

namespace App\Services\Account\Gender;

use App\Services\BaseService;
use App\Models\Contact\Gender;

class DestroyGender extends BaseService
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
        ];
    }

    /**
     * Destroy a gender.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $gender = Gender::where('account_id', $data['account_id'])
            ->findOrFail($data['gender_id']);

        $gender->delete();

        return true;
    }
}
