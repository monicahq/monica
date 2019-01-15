<?php

use App\Models\Contact\Reminder;
use Illuminate\Database\Migrations\Migration;

class ScheduleNewReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Reminder::chunk(200, function ($reminders) {
            foreach ($reminders as $reminder) {
                if (is_null($reminder->frequency_number)) {
                    $reminder->frequency_number = 1;
                    $reminder->save();
                }

                foreach ($reminder->account->users as $user) {
                    $reminder->schedule($user);
                }
            }
        });
    }
}
