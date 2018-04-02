<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddTagsToStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statistics', function ($table) {
            $table->integer('number_of_tags')->after('number_of_accounts_with_more_than_one_user')->nullable();
        });
    }
}
