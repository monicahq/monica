<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddImportJobToStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statistics', function ($table) {
            $table->integer('number_of_import_jobs')->after('number_of_accounts_with_more_than_one_user')->nullable();
        });
    }
}
