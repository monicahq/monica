<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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
        $accounts = DB::table('accounts')->get();
        foreach ($accounts as $account) {

            // EMAIL
            $id = DB::table('contact_field_types')->insert([
                'account_id' => $account->id,
                'name' => 'Email',
                'fontawesome_icon' => 'fa fa-envelope-open-o',
                'protocol' => 'mailto:',
            ]);

            $contacts = DB::table('contacts')->where('account_id', $account->id)->get();
            foreach ($contacts as $contact) {
                if (! is_null($contact->email)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $id,
                        'data' => $contact->email,
                    ]);
                }
            }

            // PHONE NUMBER
            $id = DB::table('contact_field_types')->insert([
                'account_id' => $account->id,
                'name' => 'Phone',
                'fontawesome_icon' => 'fa fa-volume-control-phone',
                'protocol' => 'tel:',
            ]);

            $contacts = DB::table('contacts')->where('account_id', $account->id)->get();
            foreach ($contacts as $contact) {
                if (! is_null($contact->phone_number)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $id,
                        'data' => $contact->phone_number,
                    ]);
                }
            }

            // FACEBOOK
            $id = DB::table('contact_field_types')->insert([
                'account_id' => $account->id,
                'name' => 'Facebook',
                'fontawesome_icon' => 'fa fa-facebook-official',
            ]);

            $contacts = DB::table('contacts')->where('account_id', $account->id)->get();
            foreach ($contacts as $contact) {
                if (! is_null($contact->facebook_profile_url)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $id,
                        'data' => $contact->facebook_profile_url,
                    ]);
                }
            }

            // TWITTER
            $id = DB::table('contact_field_types')->insert([
                'account_id' => $account->id,
                'name' => 'Twitter',
                'fontawesome_icon' => 'fa fa-twitter-square',
            ]);

            $contacts = DB::table('contacts')->where('account_id', $account->id)->get();
            foreach ($contacts as $contact) {
                if (! is_null($contact->twitter_profile_url)) {
                    DB::table('contact_fields')->insert([
                        'account_id' => $account->id,
                        'contact_id' => $contact->id,
                        'contact_field_type_id' => $id,
                        'data' => $contact->twitter_profile_url,
                    ]);
                }
            }
        }
    }
}
