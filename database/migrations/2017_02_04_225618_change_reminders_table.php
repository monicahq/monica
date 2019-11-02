<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                'deleted_at', 'people_id'
            );
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('contact_id')->after('account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                'contact_id'
            );
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('people_id')->after('account_id');
            $table->softDeletes();
        });
    }
}
