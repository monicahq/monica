<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmotionCallTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emotion_call', function (Blueprint $table) {
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('call_id');
            $table->unsignedInteger('emotion_id');
            $table->unsignedInteger('contact_id');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('emotion_id')->references('id')->on('emotions')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
