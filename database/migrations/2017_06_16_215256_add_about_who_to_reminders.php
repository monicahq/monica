<?php

use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAboutWhoToReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('is_birthday')->after('contact_id')->default('false');
            $table->string('about_object')->after('is_birthday')->nullable();
            $table->string('about_object_id')->after('about_object')->nullable();
        });

        // Migrate all kids birthdays to the new system to track birthdays reminders
        foreach (Reminder::all() as $reminder) {
            if ($reminder->kid_id) {
                $reminder->is_birthday = 'true';
                $reminder->about_object = 'kid';
                $reminder->about_object_id = $reminder->kid_id;
                $reminder->save();
            }
        }

        // Get rid of the kid_id field
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                ['kid_id']
            );
        });
    }
}
