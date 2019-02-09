<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class RemoveUsersWithoutAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')
            ->leftJoin('accounts', 'accounts.id', '=', 'users.account_id')
            ->whereNull('accounts.id')
            ->delete();
    }
}
