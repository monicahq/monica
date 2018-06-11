<?php

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
