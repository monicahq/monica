<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddConversationsToStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statistics', function ($table) {
            $table->integer('number_of_conversations')->after('number_of_import_jobs')->nullable();
            $table->integer('number_of_messages')->after('number_of_conversations')->nullable();
        });
    }
}
