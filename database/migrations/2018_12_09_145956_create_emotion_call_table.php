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
            $table->integer('account_id');
            $table->integer('call_id');
            $table->integer('emotion_id');
            $table->integer('contact_id');
            $table->timestamps();
        });
    }
}
