<?php

use App\Account;
use Illuminate\Database\Migrations\Migration;

class MigrateModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $account->populateModulesTable();
            }
        });
    }
}
