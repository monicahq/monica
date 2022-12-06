<?php

namespace App\Domains\Settings\ManageGenders\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Gender;
use App\Services\BaseService;

class UpdateGender extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'gender_id' => 'required|integer|exists:genders,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     *
     * @return array
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Update a gender.
     *
     * @param  array  $data
     * @return Gender
     */
    public function execute(array $data): Gender
    {
        $this->validateRules($data);

        $gender = $this->account()->genders()
            ->findOrFail($data['gender_id']);

        $gender->name = $data['name'];
        $gender->save();

        return $gender;
    }
}
