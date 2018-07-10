<?php

use App\Models\Contact\Contact;
use Illuminate\Database\Migrations\Migration;

class FixContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Contact::all() as $contact) {
            if ($contact->is_birthdate_approximate == 'exact' and is_null($contact->birthday_reminder_id)) {
                $contact->is_birthdate_approximate = 'approximate';
                $contact->save();
            }
        }
    }
}
