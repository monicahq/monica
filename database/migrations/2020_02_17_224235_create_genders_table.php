<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->char('type', 1)->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('genders');
    }
};
