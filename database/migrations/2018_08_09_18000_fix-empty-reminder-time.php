<?php

use App\Models\Account\Account;
use Illuminate\Database\Migrations\Migration;

class FixEmptyReminderTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Account::where('default_time_reminder_is_sent', '')
            ->update(['default_time_reminder_is_sent' => '12:00']);
    }
}
