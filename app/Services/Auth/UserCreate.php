<?php

namespace App\Services\Auth;

use App\Models\User\User;
use App\Services\BaseService;
use App\Helpers\RequestHelper;

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
            'last_name' => 'required|max:255',
            'first_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6',
            'locale' => 'nullable|string',
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
            RequestHelper::ip(),
            $data['locale'],
        );
    }
}
