<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialNetworksToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('facebook_profile_url')->after('avatar_file_name')->nullable();
            $table->string('twitter_profile_url')->after('facebook_profile_url')->nullable();
            $table->string('linkedin_profile_url')->after('twitter_profile_url')->nullable();
        });
    }
}
