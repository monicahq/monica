<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReminderToLifeEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('life_events', function (Blueprint $table) {
            $table->unsignedInteger('reminder_id')->nullable()->after('life_event_type_id');
        });
    }
}
