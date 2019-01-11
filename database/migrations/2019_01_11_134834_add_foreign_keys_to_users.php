<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the users table to make sure that we don't have
        // "ghost" users that are not associated with any account
        User::chunk(200, function ($users) {
            foreach ($users as $user) {
                try {
                    Account::findOrFail($user->account_id);
                } catch (ModelNotFoundException $e) {
                    $user->delete();
                    continue;
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
