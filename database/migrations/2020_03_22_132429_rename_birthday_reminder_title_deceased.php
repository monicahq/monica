<?php

use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
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
        /** @var Illuminate\Support\LazyCollection */
        $cursor = Contact::cursor();
        $contacts = $cursor->filter(function ($contact) {
            return $contact->is_dead && ! empty($contact->birthday_reminder_id);
        });

        foreach ($contacts as $contact) {
            $locale = $contact->account->getFirstLocale();
            Reminder::where('id', $contact->birthday_reminder_id)
                ->update([
                    'title' => trans('people.people_add_birthday_reminder_deceased', ['name' => $contact->first_name], $locale),
                ]);
        }
    }
}
