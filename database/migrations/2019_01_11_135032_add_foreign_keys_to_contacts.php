<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Contact\Contact;

class AddForeignKeysToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the contacts table to make sure that we don't have
        // "ghost" contacts that are not associated with any account
        Contact::chunk(200, function ($contacts) {
            foreach ($contacts as $contact) {
                try {
                    Account::findOrFail($contact->account_id);
                } catch (ModelNotFoundException $e) {
                    $contact->delete();
                    continue;
                }
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
