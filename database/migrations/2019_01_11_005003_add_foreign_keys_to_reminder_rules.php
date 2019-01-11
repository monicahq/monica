<?php

use App\Models\Contact\ReminderRule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeysToReminderRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the rules table to make sure that we don't have
        // "ghost" rules that are not associated with any account
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

        Schema::table('reminder_rules', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
