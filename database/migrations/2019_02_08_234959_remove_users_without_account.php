<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
