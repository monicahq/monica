<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastConsultedAtToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->datetime('last_consulted_at')->nullable()->after('linkedin_profile_url');
        });

        $contacts = DB::table('contacts')->select('id')->get();

        foreach ($contacts as $contact) {
            DB::table('contacts')
                ->where('contact_id', $contact->id)
                ->update(['last_consulted_at' => $contact->updated_at]);
        }
    }
}
