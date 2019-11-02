<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteContactFieldsFromContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique('unique_for_each_account_email_pair');
            $table->dropColumn('email');
            $table->dropColumn('phone_number');
            $table->dropColumn('street');
            $table->dropColumn('city');
            $table->dropColumn('province');
            $table->dropColumn('postal_code');
            $table->dropColumn('country_id');
            $table->dropColumn('facebook_profile_url');
            $table->dropColumn('twitter_profile_url');
        });
    }
}
