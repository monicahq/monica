<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReminderIdsToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('birthday_reminder_id')->nullable()->after('birthday_special_date_id');
            $table->foreign('birthday_reminder_id')->references('id')->on('reminders')->onDelete('set null');
            $table->unsignedInteger('first_met_reminder_id')->nullable()->after('first_met_special_date_id');
            $table->foreign('first_met_reminder_id')->references('id')->on('reminders')->onDelete('set null');
            $table->unsignedInteger('deceased_reminder_id')->nullable()->after('deceased_special_date_id');
            $table->foreign('deceased_reminder_id')->references('id')->on('reminders')->onDelete('set null');
        });
    }
}
