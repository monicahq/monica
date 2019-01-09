<?php

use App\Models\Contact\Debt;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToDebts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the debts table to make sure that we don't have
        // "ghost" debts that are not associated with any account
        Debt::chunk(200, function ($debts) {
            foreach ($debts as $debt) {
                try {
                    Account::findOrFail($debt->account_id);
                    Contact::findOrFail($debt->contact_id);
                } catch (ModelNotFoundException $e) {
                    $debt->delete();
                    continue;
                }
            }
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
