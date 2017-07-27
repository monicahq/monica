<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddInvitationsStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function ($table) {
            $table->integer('number_of_invitations_sent')->after('api_key')->nullable();
        });

        Schema::table('statistics', function ($table) {
            $table->integer('number_of_invitations_sent')->after('number_of_kids')->nullable();
            $table->integer('number_of_accounts_with_more_than_one_user')->after('number_of_invitations_sent')->nullable();
        });
    }
}
