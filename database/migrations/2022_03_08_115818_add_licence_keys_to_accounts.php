<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLicenceKeysToAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('licence_key')->after('uuid')->nullable();
            $table->datetime('valid_until_at')->after('licence_key')->nullable();
            $table->string('purchaser_email')->after('valid_until_at')->nullable();
            $table->string('frequency')->after('purchaser_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('licence_keys');
            $table->dropColumn('valid_until_at');
        });
    }
}
