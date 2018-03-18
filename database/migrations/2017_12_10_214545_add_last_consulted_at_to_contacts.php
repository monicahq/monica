<?php

use Illuminate\Support\Facades\DB;
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
            $table->timestamp('last_consulted_at')->nullable()->after('linkedin_profile_url');
        });

        $contacts = DB::table('contacts')->select('id', 'updated_at')->get();

        foreach ($contacts as $contact) {
            if ($contact->updated_at) {
                DB::table('contacts')
                ->where('id', $contact->id)
                ->update(['last_consulted_at' => $contact->updated_at]);
            } else {
                DB::table('contacts')
                ->where('id', $contact->id)
                ->update(['last_consulted_at' => $contact->created_at]);
            }
        }
    }
}
