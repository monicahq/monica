<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
