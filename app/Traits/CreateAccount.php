<?php

namespace App\Traits;

use App\Account;
use App\Helpers\RandomHelper;
use App\Jobs\SendNewUserAlert;
use App\User;
use Carbon\Carbon;

trait CreateAccount
{
    public function createAccount(array $data)
    {
        $user = new User;
        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];
        $user->timezone = config('app.timezone');
        $user->created_at = Carbon::now();

        //ensure password is set
        if(array_key_exists('password', $data))
        {
            $user->password = bcrypt($data['password']);
        }

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
