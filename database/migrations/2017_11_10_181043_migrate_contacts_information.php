<?php

use App\Account;
use App\Instance;
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
