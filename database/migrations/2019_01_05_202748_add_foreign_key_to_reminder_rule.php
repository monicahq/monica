<?php

use App\Models\Account\Account;
use App\Models\Contact\ReminderRule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeyToReminderRule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the reminder rules table to make sure that we don't
        // have "ghost" reminder rules that are not associated with any contact
        // (as it's the case in production)
        ReminderRule::chunk(200, function ($reminderRules) {
            foreach ($reminderRules as $reminderRule) {
                try {
                    Account::findOrFail($reminderRule->account_id);
                } catch (ModelNotFoundException $e) {
                    $reminderRule->delete();
                    continue;
                }
            }
        });

        Schema::disableForeignKeyConstraints();
        Schema::table('reminder_rules', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        Schema::enableForeignKeyConstraints();
    }
}
