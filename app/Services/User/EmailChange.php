<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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
