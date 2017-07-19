<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReminderUserTemp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminder_user_temp', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('reminder_id');
            $table->boolean('done')->default(0);
            $table->timestamps();
        });
    }
}
