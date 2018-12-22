<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Contact\Reminder;

class AddReminderInitialDateToReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->date('reminder_intial_date')->after('description');
        });

        // we need to migrate old data. Since we don't know what was the initial
        // date for the reminder, we need to make a guess by taking the last
        // triggered column information.
        Reminder::chunk(200, function ($reminders) {
            foreach ($reminders as $reminder) {
                $reminder->reminder_initial_date = $reminder->last_triggered;
                $reminder->save();
            }
        });
    }
}
