<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LunarCalendarSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('calendar_type', 5)->after('frequency_type')->nullable();
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('calendar_type', 5)->after('birthday_special_date_id')->nullable();
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
            $table->dropColumn('calendar_type');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('calendar_type');
        });
    }
}
