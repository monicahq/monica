<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddressbookSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addressbook_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('user_id');

            $table->string('uri', 2096);
            $table->string('username', 1024);
            $table->string('password', 2048);
            $table->boolean('readonly');
            $table->string('capabilities', 2048);
            $table->string('syncToken', 512)->nullable();
            $table->string('localSyncToken', 1024)->nullable();
            $table->smallInteger('frequency')->default(180); // 3 hours
            $table->timestamp('lastsync', 0)->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addressbook_subscriptions');
    }
}
