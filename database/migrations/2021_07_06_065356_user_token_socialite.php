<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('driver_id', 256);
            $table->string('driver', 50);
            $table->char('format', 6);
            $table->string('email', 1024)->nullable();
            $table->string('token', 2048);
            $table->string('token_secret', 2048)->nullable();
            $table->string('refresh_token', 2048)->nullable();
            $table->unsignedBigInteger('expires_in')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['driver', 'driver_id']);
        });
    }
};
