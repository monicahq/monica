<?php

use App\Models\Contact\Note;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the notes table to make sure that we don't have
        // "ghost" notes that are not associated with any account
        Note::chunk(200, function ($notes) {
            foreach ($notes as $note) {
                try {
                    Account::findOrFail($note->account_id);
                    Contact::findOrFail($note->contact_id);
                } catch (ModelNotFoundException $e) {
                    $note->delete();
                    continue;
                }
            }
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
