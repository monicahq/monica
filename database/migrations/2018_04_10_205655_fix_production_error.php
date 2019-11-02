<?php

use App\Models\User\User;
use Illuminate\Support\Str;
use App\Models\Account\Account;
use Illuminate\Database\Migrations\Migration;

class FixProductionError extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // this migration fixes a weird issue where we have a bunch of users
        // who don't have an account_id
        $usersWithoutAccount = collect();
        User::chunk(200, function ($users) use ($usersWithoutAccount) {
            foreach ($users as $user) {
                $account = $user->account;
                if (is_null($account)) {
                    $usersWithoutAccount->push($user);
                }
            }
        });

        foreach ($usersWithoutAccount as $user) {
            // creation of a new account
            $account = new Account;
            $account->api_key = Str::random(30);
            $account->created_at = now();
            $account->save();

            $user->account_id = $account->id;
            $user->save();
        }
    }
}
