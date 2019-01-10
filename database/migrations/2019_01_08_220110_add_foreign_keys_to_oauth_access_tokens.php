<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToOauthAccessTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $accessTokens = DB::table('oauth_access_tokens')->get();

        foreach ($accessTokens as $accessToken) {
            $client = DB::table('oauth_clients')->where('id', $accessToken->client_id)->first();
            if (count($client) == 0) {
                DB::table('oauth_clients')->where('id', $accessToken->client_id)->delete();
            }
        }

        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->unsignedInteger('client_id')->change();
            $table->foreign('client_id')->references('id')->on('oauth_clients')->onDelete('cascade');
        });
    }
}
