<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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

            $table->string('description', 500)->nullable();
            $table->string('name', 100);
            $table->timestamps();

            $table->index('name');
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
