<?php

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Schema;
use App\Models\Contact\ActivityStatistic;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToActivityStatistics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the activities table to make sure that we don't have
        // "ghost" activities that are not associated with any account
        ActivityStatistic::chunk(200, function ($activityStatistics) {
            foreach ($activityStatistics as $activityStatistic) {
                try {
                    Account::findOrFail($activityStatistic->account_id);
                    Contact::findOrFail($activityStatistic->contact_id);
                } catch (ModelNotFoundException $e) {
                    $activityStatistic->delete();
                    continue;
                }
            }
        });

        Schema::table('activity_statistics', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
