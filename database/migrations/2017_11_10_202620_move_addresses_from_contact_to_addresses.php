<?php

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
