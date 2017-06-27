<?php

namespace App\Helpers;


use App\Account;
use App\Jobs\SendNewUserAlert;
use App\User;
use Carbon\Carbon;

class CreateUserAccount
{
    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    public static function create(array $data)
    {
        $user = new User;
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->timezone = config('app.timezone');
        $user->created_at = Carbon::now();
        $user->save();

        // create a new account
        $account = new Account;
        $account->api_key = RandomHelper::generateString(30);
        $account->created_at = Carbon::now();
        $account->save();

        $user->account_id = $account->id;
        $user->save();

        // send me an alert
        dispatch(new SendNewUserAlert($user));

        return $user;
    }
}