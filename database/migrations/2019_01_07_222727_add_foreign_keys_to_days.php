<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Journal\Day;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToDays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the days table to make sure that we don't have
        // "ghost" days that are not associated with any account
        Day::chunk(200, function ($days) {
            foreach ($days as $day) {
                try {
                    Account::findOrFail($day->account_id);
                } catch (ModelNotFoundException $e) {
                    $day->delete();
                    continue;
                }
            }
        });

        Schema::table('days', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
