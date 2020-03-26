<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressBook extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addressbooks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('user_id');

            $table->string('name', 500)->nullable();
            $table->string('addressBookId', 100);
            $table->timestamps();

            $table->index('addressBookId');
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
        Schema::dropIfExists('addressbooks');
    }
}
