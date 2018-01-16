<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdditionalRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminders_to_send', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('reminder_id');
            $table->datetime('trigger_date');
            $table->boolean('is_reminder_of_reminder')->default(false);
            $table->timestamps();
        });

        Schema::create('reminders_sent', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('reminder_id');
            $table->datetime('sent_date');
            $table->boolean('is_reminder_of_reminder')->default(false);
            $table->string('scheduled_number_days_before')->nullable();
            $table->string('scheduled_time_to_send')->nullable();
            $table->timestamps();
        });

        Schema::create('account_reminder_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('number_of_days_before');
            $table->integer('time_to_send');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders_to_send');
        Schema::dropIfExists('reminders_sent');
        Schema::dropIfExists('account_reminder_rules');
    }
}
