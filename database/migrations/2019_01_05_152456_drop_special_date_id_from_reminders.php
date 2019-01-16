<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropSpecialDateIdFromReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('special_date_id');
            $table->dropColumn('last_triggered');
            $table->dropColumn('next_expected_date');
        });

        Schema::table('special_dates', function (Blueprint $table) {
            $table->dropColumn('reminder_id');
        });
    }
}
