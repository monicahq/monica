<?php

use Illuminate\Support\Facades\DB;
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
            $table->boolean('is_a_birthday')->after('is_birthday');
        });

        $reminders = DB::table('reminders')->get();

        foreach ($reminders as $reminder) {
            if ($reminder->is_birthday == 'true') {
                DB::table('reminders')
                    ->where('id', $reminder->id)
                    ->update(['is_a_birthday' => 1]);
            } else {
                DB::table('reminders')
                    ->where('id', $reminder->id)
                    ->update(['is_a_birthday' => 0]);
            }
        }

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('is_birthday');
        });

        Schema::table('reminders', function ($table) {
            $table->renameColumn('is_a_birthday', 'is_birthday');
        });
    }
}
