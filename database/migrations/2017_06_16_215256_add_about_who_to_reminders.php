<?php

use App\Contact;
use App\Reminder;
use App\SignificantOther;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAboutWhoToReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('is_birthday')->after('contact_id')->default('false');
            $table->string('about_object')->after('is_birthday')->nullable();
            $table->string('about_object_id')->after('about_object')->nullable();
        });

        // Migrate all kids birthdays to the new system to track birthdays reminders
        foreach (Reminder::all() as $reminder) {
            if ($reminder->kid_id) {
                $reminder->is_birthday = 'true';
                $reminder->about_object = 'kid';
                $reminder->about_object_id = $reminder->kid_id;
                $reminder->save();
            }
        }

        // Get rid of the kid_id field
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                ['kid_id']
            );
        });

        // Fix mistakes in the significant others table
        foreach (SignificantOther::all() as $significantOther) {
            if ($significantOther->is_birthdate_approximate == 'exact' and is_null($significantOther->birthdate)) {
                $significantOther->is_birthdate_approximate = 'unknown';
                $significantOther->save();
            }

            if ($significantOther->is_birthdate_approximate == 'exact' and is_null($significantOther->birthday_reminder_id)) {
                $significantOther->is_birthdate_approximate = 'approximate';
                $significantOther->save();
            }

            if (! is_null($significantOther->birthday_reminder_id)) {
                $reminder = $significantOther->reminder;
                if (is_null($reminder)) {
                    $significantOther->birthday_reminder_id = null;
                    $significantOther->save();
                } else {
                    $reminder->is_birthday = 'true';
                    $reminder->about_object = 'significantother';
                    $reminder->about_object_id = $significantOther->id;
                    $reminder->save();
                }
            }
        }

        foreach (Contact::all() as $contact) {
            // Fix mistakes that are in the database. An old bug introduced exact dates
            // without birthdates, which is not possible.
            if ($contact->is_birthdate_approximate == 'exact' and is_null($contact->birthdate)) {
                $contact->is_birthdate_approximate = 'unknown';
                $contact->save();
            }

            // Make sure that contacts with birthday_reminder_id set haven't deleted
            // the reminders yet. If they did delete it, we need to make sure that
            // birthday_reminder_id is set to null.
            if (! is_null($contact->birthday_reminder_id)) {
                $reminder = $contact->reminders->find($contact->birthday_reminder_id);
                if (is_null($reminder)) {
                    $contact->birthday_reminder_id = null;
                    $contact->save();
                } else {
                    $reminder->is_birthday = 'true';
                    $reminder->about_object = 'contact';
                    $reminder->about_object_id = $reminder->contact_id;
                    $reminder->save();
                }
            }
        }
    }
}
