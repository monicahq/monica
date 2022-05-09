<?php

use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\DB;
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
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('reminder_id')->nullable();
            $table->datetime('trigger_date');
            $table->integer('scheduled_number_days_before')->nullable();
            $table->timestamps();
        });

        Schema::create('reminders_sent', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('reminder_id')->nullable();
            $table->mediumText('title');
            $table->longText('description');
            $table->longText('html_sent_content');
            $table->datetime('sent_date');
            $table->integer('scheduled_number_days_before')->nullable();
            $table->timestamps();
        });

        Schema::create('reminder_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('number_of_days_before');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('default_time_reminder_is_sent')->after('number_of_invitations_sent')->default('12:00');
        });

        $accounts = DB::table('accounts')->select('id')->get();
        foreach ($accounts as $account) {
            DB::table('reminder_rules')->insert([
                ['account_id' => $account->id, 'number_of_days_before' => 7],
                ['account_id' => $account->id, 'number_of_days_before' => 30],
            ]);
        }

        // Create notifications for existing reminders
        // Only create notifications for reminders that are not weekly based
        $reminders = Reminder::where('frequency_type', '!=', 'week')->get();
        foreach ($reminders as $reminder) {
            $reminder->scheduleNotifications($reminder->calculateNextExpectedDate(), $reminder->account->users()->first());
        }
    }
}
