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
            $table->string('calendar_type')->nullable();
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('calendar_type')->nullable();
        });
    }
}
