<?php

namespace App\Services\User;

use App\Models\User\User;
use App\Services\BaseService;

class EmailChange extends BaseService
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
            'email' => 'required|email|unique:users',
            'user_id' => 'required|integer',
        ];
    }

    /**
     * Update email of the user.
     *
     * @param array $data
     * @return User
     */
    public function execute(array $data) : User
    {
        $this->validate($data);

        $user = User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        // Change email of the user
        $user->email = $data['email'];

        if (config('monica.signup_double_optin')) {
            // Resend validation token
            $user->email_verified_at = null;
            $user->save();

            $user->sendEmailVerificationNotification();
        } else {
            $user->save();
        }

        return $user;
    }
}
