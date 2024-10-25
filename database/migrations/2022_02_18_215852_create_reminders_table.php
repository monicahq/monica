<?php

use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
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
        Schema::create('contact_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->integer('day')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->string('type');
            $table->integer('frequency_number')->nullable();
            $table->datetime('last_triggered_at')->nullable();
            $table->integer('number_times_triggered')->default(0);
            $table->timestamps();
        });

        Schema::create('user_notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->string('type');
            $table->string('label')->nullable();
            $table->text('content');
            $table->time('preferred_time')->nullable();
            $table->boolean('active')->default(false);
            $table->datetime('verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_reminder_scheduled', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserNotificationChannel::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ContactReminder::class)->constrained()->cascadeOnDelete();
            $table->datetime('scheduled_at');
            $table->datetime('triggered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_notification_sent', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserNotificationChannel::class)->constrained()->cascadeOnDelete();
            $table->datetime('sent_at');
            $table->string('subject_line');
            $table->text('payload')->nullable();
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
        Schema::dropIfExists('user_notification_sent');
        Schema::dropIfExists('contact_reminder_scheduled');
        Schema::dropIfExists('user_notification_channels');
        Schema::dropIfExists('contact_reminders');
    }
};
