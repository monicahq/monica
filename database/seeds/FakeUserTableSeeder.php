<?php

use Illuminate\Database\Seeder;

class FakeUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // populate account table
        $accountID = DB::table('accounts')->insertGetId([
            'api_key' => str_random(30),
        ]);

        // populate user table
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountID,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
            'timezone' => config('app.timezone'),
            'remember_token' => str_random(10),
        ]);

        // create the second test, blank account
        $accountID = DB::table('accounts')->insertGetId([
            'api_key' => str_random(30),
        ]);

        // populate user table
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountID,
            'first_name' => 'Blank',
            'last_name' => 'State',
            'email' => 'blank@blank.com',
            'password' => bcrypt('blank'),
            'timezone' => config('app.timezone'),
            'remember_token' => str_random(10),
        ]);
    }
}
