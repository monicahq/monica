<?php

use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use App\Models\Contact\ContactFieldType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveLinkedinDataToContactFieldType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contacts = Contact::select('id', 'linkedin_profile_url', 'account_id')
            ->whereNotNull('linkedin_profile_url')
            ->chunk(50, function ($contacts) {
                foreach ($contacts as $contact) {
                    $contactFieldType = ContactFieldType::where('account_id', $contact->account_id)
                                                        ->where('name', 'LinkedIn')
                                                        ->first();

                    $contact->contactFields()->create([
                        'account_id' => $contact->account_id,
                        'contact_field_type_id' => $contactFieldType->id,
                        'data' => $contact->linkedin_profile_url,
                    ]);
                }
            });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('linkedin_profile_url');
        });
    }
}
