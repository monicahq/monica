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
        Schema::create('contact_feed_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('contact_id');
            $table->string('action');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('feedable_id')->nullable();
            $table->string('feedable_type')->nullable();
            $table->timestamps();
            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contact_feed_items');
    }
};
