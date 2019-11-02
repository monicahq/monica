<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAccountInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('account_id')->after('remember_token');
            $table->string('send_sms_alert')->default('false')->after('account_id');
            $table->integer('phone_number')->nullable()->after('send_sms_alert');
            $table->integer('amazon_store_country_id')->nullable()->after('phone_number');
            $table->string('timezone')->nullable()->after('amazon_store_country_id');
            $table->string('locale')->default('en')->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function ($table) {
            $table->dropColumn(['account_id', 'send_sms_alert', 'phone_number', 'amazon_store_country_id']);
        });
    }
}
