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

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class MoveAddressesFromContactToAddresses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contacts = DB::table('contacts')->select('account_id', 'id', 'street', 'city', 'province', 'postal_code', 'country_id')->get();
        foreach ($contacts as $contact) {
            if (! is_null($contact->street) or ! is_null($contact->city) or ! is_null($contact->province) or ! is_null($contact->postal_code) or ! is_null($contact->country_id)) {
                $id = DB::table('addresses')->insertGetId([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'name' => 'default',
                    'street' => (is_null($contact->street) ? null : $contact->street),
                    'city' => (is_null($contact->city) ? null : $contact->city),
                    'province' => (is_null($contact->province) ? null : $contact->province),
                    'postal_code' => (is_null($contact->postal_code) ? null : $contact->postal_code),
                    'country_id' => (is_null($contact->country_id) ? null : $contact->country_id),
                ]);
            }
        }
    }
}
