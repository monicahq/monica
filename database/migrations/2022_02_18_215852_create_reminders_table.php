<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
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
            $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        Schema::create('user_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->string('label');
            $table->text('content');
            $table->time('preferred_time')->nullable();
            $table->boolean('active')->default(false);
            $table->datetime('verified_at')->nullable();
            $table->string('email_verification_link')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('scheduled_contact_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_reminder_id');
            $table->unsignedBigInteger('user_notification_channel_id');
            $table->datetime('scheduled_at');
            $table->datetime('triggered_at')->nullable();
            $table->timestamps();
            $table->foreign('contact_reminder_id')->references('id')->on('contact_reminders')->onDelete('cascade');
            $table->foreign('user_notification_channel_id')->references('id')->on('user_notification_channels')->onDelete('cascade');
        });

        Schema::create('user_notification_sent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_notification_channel_id')->nullable();
            $table->datetime('sent_at');
            $table->text('subject_line');
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
        Schema::dropIfExists('scheduled_contact_reminders');
        Schema::dropIfExists('user_notification_channels');
        Schema::dropIfExists('user_notification_sent');
    }
};
