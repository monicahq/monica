<?php

use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePredefinedReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $reminders = Reminder::all();
        foreach ($reminders as $reminder) {
            echo $reminder->id.' ';
            if (! is_null($reminder->title)) {
                $reminder->title = decrypt($reminder->title);
            }

            if (! is_null($reminder->description)) {
                $reminder->description = decrypt($reminder->description);
            }

            $reminder->save();
        }

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                'reminder_type_id'
            );
        });

        Schema::drop('reminder_types');
    }
}
