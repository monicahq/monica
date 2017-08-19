<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RefactorUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_user_id',
                'access_token',
                'amazon_store_country_id',
                'onboarding_journal_dismissed',
                'send_sms_alert',
                'phone_number',
                'gender',
                'deleted_at',
            ]);

            $table->integer('invited_by_user_id')->after('contacts_sort_order')->nullable();
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('invited_by_user_id');
            $table->string('email');
            $table->string('invitation_key');
            $table->timestamps();
        });
    }
}
