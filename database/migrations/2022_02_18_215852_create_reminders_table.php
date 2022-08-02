<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('contact_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('label');
            $table->integer('day')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->string('type');
            $table->integer('frequency_number')->nullable();
            $table->datetime('last_triggered_at')->nullable();
            $table->integer('number_times_triggered')->default(0);
            $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        Schema::create('user_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->string('label')->nullable();
            $table->text('content');
            $table->time('preferred_time')->nullable();
            $table->boolean('active')->default(false);
            $table->datetime('verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('contact_reminder_scheduled', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_notification_channel_id');
            $table->unsignedBigInteger('contact_reminder_id');
            $table->datetime('scheduled_at');
            $table->datetime('triggered_at')->nullable();
            $table->timestamps();
            $table->foreign('user_notification_channel_id')->references('id')->on('user_notification_channels')->onDelete('cascade');
            $table->foreign('contact_reminder_id')->references('id')->on('contact_reminders')->onDelete('cascade');
        });

        Schema::create('user_notification_sent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_notification_channel_id')->nullable();
            $table->datetime('sent_at');
            $table->string('subject_line');
            $table->text('payload')->nullable();
            $table->timestamps();
            $table->foreign('user_notification_channel_id')->references('id')->on('user_notification_channels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_reminders');
        Schema::dropIfExists('user_notification_channels');
        Schema::dropIfExists('contact_reminder_scheduled');
        Schema::dropIfExists('user_notification_sent');
    }
};
