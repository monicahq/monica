<?php

use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
