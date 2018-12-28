<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToSpecialDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SpecialDate::chunk(200, function ($specialDates) {
            foreach ($specialDates as $specialDate) {
                try {
                    Contact::findOrFail($specialDate->contact_id);
                } catch (ModelNotFoundException $e) {
                    $specialDate->delete();
                    continue;
                }
            }
        });

        SpecialDate::chunk(200, function ($specialDates) {
            foreach ($specialDates as $specialDate) {
                try {
                    Account::findOrFail($specialDate->account_id);
                } catch (ModelNotFoundException $e) {
                    $specialDate->delete();
                    continue;
                }
            }
        });

        Schema::table('special_dates', function (Blueprint $table) {
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
