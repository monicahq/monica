<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Services\BaseService;

class UserCreate extends BaseService
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
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6',
            'locale' => 'nullable',
            'ip_address' => 'nullable',
        ];
    }

    /**
     * Create a user.
     *
     * @param array $data
     * @return User
     */
    public function execute(array $data) : User
    {
        $this->validate($data);

        return User::createDefault(
            $data['account_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password'],
            $data['ip_address'],
            $data['locale'],
        );
    }
}
