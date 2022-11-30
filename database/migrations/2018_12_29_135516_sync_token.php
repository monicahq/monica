<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('synctokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('user_id');
            $table->string('name')->default('contacts');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['account_id', 'user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('synctokens');
    }
};
