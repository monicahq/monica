<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Gift;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToGifts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the gifts table to make sure that we don't have
        // "ghost" gifts that are not associated with any account
        Gift::chunk(200, function ($gifts) {
            foreach ($gifts as $gift) {
                try {
                    Account::findOrFail($gift->account_id);
                    Contact::findOrFail($gift->contact_id);
                } catch (ModelNotFoundException $e) {
                    $gift->delete();
                    continue;
                }
            }
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
