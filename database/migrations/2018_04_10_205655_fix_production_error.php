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



use App\Models\User\User;
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
            $account->api_key = str_random(30);
            $account->created_at = now();
            $account->save();

            $user->account_id = $account->id;
            $user->save();
        }
    }
}
