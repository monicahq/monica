<?php

use Illuminate\Database\Migrations\Migration;

class RenameBirthdayReminderTitleDeceased extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $affected = DB::update(
            'update reminders '.
            'inner join contacts '.
                'on reminders.id=birthday_reminder_id '.
            "set reminders.title = concat('On this date, ', contacts.first_name, ', would have celebrated his birthday') ".
            'where is_dead = 1;'
        );
    }
}
