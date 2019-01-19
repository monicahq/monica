<?php

use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MigratePreviousRemiders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to migrate old data. Since we don't know what was the initial
        // date for the reminder, we need to make a guess by taking the last
        // triggered column information.
        // we need to migrate existing birthday reminders
        Contact::whereNotNull('birthday_special_date_id')
            ->chunk(50, function ($contacts) {
                foreach ($contacts as $contact) {
                    try {
                        $specialDate = SpecialDate::findOrFail($contact->birthday_special_date_id);

                        try {
                            $reminder = Reminder::findOrFail($specialDate->reminder_id);
                            $contact->birthday_reminder_id = $reminder->id;
                            $contact->save();
                        } catch (ModelNotFoundException $e) {
                            $contact->birthday_special_date_id = null;
                            $contact->birthday_reminder_id = null;
                            $contact->save();
                        }
                    } catch (ModelNotFoundException $e) {
                        $contact->birthday_special_date_id = null;
                        $contact->birthday_reminder_id = null;
                        $contact->save();
                    }
                }
            });
    }
}
