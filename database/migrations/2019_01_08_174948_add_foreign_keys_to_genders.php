<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToGenders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the genders table to make sure that we don't have
        // "ghost" genders that are not associated with any account
        Gender::chunk(200, function ($genders) {
            foreach ($genders as $gender) {
                try {
                    Account::findOrFail($gender->account_id);
                } catch (ModelNotFoundException $e) {
                    $gender->delete();
                    continue;
                }
            }
        });

        Schema::table('genders', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
