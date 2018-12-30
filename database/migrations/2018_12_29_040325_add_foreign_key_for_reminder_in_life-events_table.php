<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyForReminderInLifeEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('life_events', function (Blueprint $table) {
            $table->unsignedInteger('reminder_id')->change();
            $table->foreign('reminder_id')->references('id')->on('reminders')->onDelete('set null');
        });
    }
}
