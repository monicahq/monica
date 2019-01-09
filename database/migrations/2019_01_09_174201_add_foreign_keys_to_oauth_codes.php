<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Gift;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Account\ImportJob;
use App\Models\Account\ImportJobReport;
use App\Models\User\User;
use App\Models\Account\Invitation;
use App\Models\Journal\JournalEntry;
use App\Models\User\Module;
use App\Models\Contact\Note;
use Illuminate\Support\Facades\DB;

class AddForeignKeysToOauthCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $oauthCodes = DB::table('oauth_auth_codes')->get();

        foreach ($oauthCodes as $oauthCode) {
            try {
                User::findOrFail($oauthCode->user_id);
            } catch (ModelNotFoundException $e) {
                DB::table('oauth_auth_codes')->where('id', $oauthCode->id)->delete();
                continue;
            }

            $client = DB::table('oauth_clients')->where('id', $oauthCode->client_id)->first();
            if (count($client) == 0) {
                DB::table('oauth_clients')->where('id', $oauthCode->client_id)->delete();
            }
        }

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->change();
            $table->unsignedInteger('client_id')->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });
    }
}
