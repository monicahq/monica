<?php

use App\Models\Account\Account;
use Illuminate\Database\Migrations\Migration;

class FixInconsistantReminderTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                if (strlen($account->default_time_reminder_is_sent) == 4) {
                    $account->default_time_reminder_is_sent = date('H:i', strtotime($account->default_time_reminder_is_sent));
                    $account->save();
                }
            }
        });
    }
}
