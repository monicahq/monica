<?php

use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Account\Invitation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToInvitations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the invitations table to make sure that we don't have
        // "ghost" invitations that are not associated with any account
        Invitation::chunk(200, function ($invitations) {
            foreach ($invitations as $invitation) {
                try {
                    Account::findOrFail($invitation->account_id);
                    User::findOrFail($invitation->invited_by_user_id);
                } catch (ModelNotFoundException $e) {
                    $invitation->delete();
                    continue;
                }
            }
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('user_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invited_by_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
