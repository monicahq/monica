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


use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Database\Migrations\Migration;

class FixWrongGender extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contacts = Contact::where('gender_id', 0)->where('account_id', '!=', 0)->get();
        foreach ($contacts as $contact) {
            $account = Account::find($contact->account_id);
            $firstGender = Gender::where('account_id', $account->id)->first();
            $contact->gender_id = $firstGender->id;
            $contact->save();
        }
    }
}
