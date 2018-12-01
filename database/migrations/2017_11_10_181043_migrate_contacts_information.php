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


use App\Models\Account\Account;
use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class MigrateContactsInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $accounts = Account::select('id')->get();

        foreach ($accounts as $account) {
            $contacts = DB::table('contacts')->where('account_id', $account->id)->get();

            $account->populateContactFieldTypeTable();

            // EMAIL
            $emailId = DB::table('contact_field_types')->where('account_id', $account->id)
                                                        ->where('type', 'email')
                                                        ->first();

            // PHONE NUMBER
            $idPhoneNumber = DB::table('contact_field_types')->where('account_id', $account->id)
                                                        ->where('type', 'phone')
                                                        ->first();

            // FACEBOOK
            $idFacebook = DB::table('contact_field_types')->where('account_id', $account->id)
                                                        ->where('name', 'Facebook')
                                                        ->first();

            // TWITTER
            $idTwitter = DB::table('contact_field_types')->where('account_id', $account->id)
                                                        ->where('name', 'Twitter')
                                                        ->first();

            foreach ($contacts as $contact) {
                if (! is_null($contact->email)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $emailId->id,
                        'data' => $contact->email,
                        'created_at' => now(),
                    ]);
                }

                if (! is_null($contact->phone_number)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $idPhoneNumber->id,
                        'data' => $contact->phone_number,
                        'created_at' => now(),
                    ]);
                }

                if (! is_null($contact->facebook_profile_url)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $idFacebook->id,
                        'data' => $contact->facebook_profile_url,
                        'created_at' => now(),
                    ]);
                }

                if (! is_null($contact->twitter_profile_url)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $idTwitter->id,
                        'data' => $contact->twitter_profile_url,
                        'created_at' => now(),
                    ]);
                }
            }
        }

        $instance = Instance::first();
        $instance->markDefaultContactFieldTypeAsMigrated();
    }
}
