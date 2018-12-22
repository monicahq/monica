<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Contact\Reminder;

class AddReminderInitialDateToReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->date('reminder_intial_date')->after('description');
            $table->boolean('enable_notification_seven_days_prior')->default(true)->after('frequency_number');
            $table->boolean('enable_notification_thirty_days_prior')->default(true)->after('frequency_number');
        });

        // we need to migrate old data. Since we don't know what was the initial
        // date for the reminder, we need to make a guess by taking the last
        // triggered column information.
        Reminder::chunk(200, function ($reminders) {
            foreach ($reminders as $reminder) {
                $reminder->reminder_initial_date = $reminder->last_triggered;
                $reminder->save();
            }
        });

        Schema::create('reminder_outbox', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('reminder_id');
            $table->datetime('trigger_date');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->char('country', 3)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('reminder_id')->references('id')->on('reminders')->onDelete('cascade');
        });
    }
}
