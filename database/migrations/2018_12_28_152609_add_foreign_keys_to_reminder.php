<?php

use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToReminder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the reminders table to make sure that we don't have
        // "ghost" reminders that are not associated with any contact (as it's
        // the case in production)
        Reminder::chunk(200, function ($reminders) {
            foreach ($reminders as $reminder) {
                try {
                    Contact::findOrFail($reminder->contact_id);
                } catch (ModelNotFoundException $e) {
                    $reminder->delete();
                    continue;
                }
            }
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
