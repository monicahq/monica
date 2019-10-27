<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNumberTasksContact extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'number_of_tasks'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('number_of_tasks_in_progress')->after('number_of_gifts_offered');
            $table->integer('number_of_tasks_completed')->after('number_of_tasks_in_progress');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'number_of_tasks_completed', 'number_of_gifts_offered'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('number_of_tasks');
        });
    }
}
