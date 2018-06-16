<?php

use App\Models\Contact\Contact;
use Illuminate\Database\Migrations\Migration;

class RemoveContactEncryption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contacts = Contact::all();
        foreach ($contacts as $contact) {
            echo $contact->id;
            if (! is_null($contact->email)) {
                $contact->email = decrypt($contact->email);
            }

            if (! is_null($contact->phone_number)) {
                $contact->phone_number = decrypt($contact->phone_number);
            }

            if (! is_null($contact->street)) {
                $contact->street = decrypt($contact->street);
            }

            if (! is_null($contact->city)) {
                $contact->city = decrypt($contact->city);
            }

            if (! is_null($contact->province)) {
                $contact->province = decrypt($contact->province);
            }

            if (! is_null($contact->postal_code)) {
                $contact->postal_code = decrypt($contact->postal_code);
            }

            if ($contact->is_birthdate_approximate == 'true') {
                $contact->is_birthdate_approximate = 'approximate';
            }

            if ($contact->is_birthdate_approximate == 'false') {
                $contact->is_birthdate_approximate = 'exact';
            }
            $contact->save();
        }
    }
}
