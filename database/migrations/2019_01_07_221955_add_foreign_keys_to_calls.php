<?php

use App\Models\Contact\Call;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToCalls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the calls table to make sure that we don't have
        // "ghost" calls that are not associated with any account
        Call::chunk(200, function ($calls) {
            foreach ($calls as $call) {
                try {
                    Account::findOrFail($call->account_id);
                    Contact::findOrFail($call->contact_id);
                } catch (ModelNotFoundException $e) {
                    $call->delete();
                    continue;
                }
            }
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
