<?php

namespace App\Domains\Settings\ManageGenders\Services;

use App\Interfaces\ServiceInterface;
use App\Models\Gender;
use App\Services\BaseService;

class CreateGender extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'author_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'author_must_be_account_administrator',
        ];
    }

    /**
     * Create a gender.
     */
    public function execute(array $data): Gender
    {
        $this->validateRules($data);

        $gender = Gender::create([
            'account_id' => $data['account_id'],
            'name' => $data['name'],
        ]);

        return $gender;
    }
}
