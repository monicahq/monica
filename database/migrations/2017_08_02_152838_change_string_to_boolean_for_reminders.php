<?php

use App\Reminder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStringToBooleanForReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // create new column
        Schema::table('reminders', function ($table) {
            $table->boolean('is_a_birthday')->after('is_birthday')->nullable();
        });

        foreach (Reminder::all() as $reminder) {
            if ($reminder->is_birthday == 'true') {
                $reminder->is_a_birthday = 1;
            } else {
                $reminder->is_a_birthday = 0;
            }
            $reminder->save();
        }

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('is_birthday');
        });

        Schema::table('reminders', function ($table) {
            $table->renameColumn('is_a_birthday', 'is_birthday');
        });
    }
}
