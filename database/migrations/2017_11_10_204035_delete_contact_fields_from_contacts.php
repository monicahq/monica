<?php

use App\Traits\MigrationHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeleteContactFieldsFromContacts extends Migration
{
    use MigrationHelper;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driverName = $this->getDriverName();
        if ($driverName != 'sqlite') {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropUnique('unique_for_each_account_email_pair');
            });
        }
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('phone_number');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('street');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('city');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('province');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('postal_code');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('facebook_profile_url');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('twitter_profile_url');
        });
    }
}
