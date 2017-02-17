<?php

use App\Kid;
use App\Gift;
use App\Note;
use App\Task;
use App\Event;
use App\Activity;
use App\Reminder;
use App\SignificantOther;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveTempFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Activity::unsetEventDispatcher();
        foreach (Activity::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        Event::unsetEventDispatcher();
        foreach (Event::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        Gift::unsetEventDispatcher();
        foreach (Gift::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        Kid::unsetEventDispatcher();
        foreach (Kid::all() as $object) {
            $object->child_of_contact_id = $object->temp_people_id;
            $object->save();
        }

        Note::unsetEventDispatcher();
        foreach (Note::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        Reminder::unsetEventDispatcher();
        foreach (Reminder::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        SignificantOther::unsetEventDispatcher();
        foreach (SignificantOther::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        Task::unsetEventDispatcher();
        foreach (Task::all() as $object) {
            $object->contact_id = $object->temp_people_id;
            $object->save();
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('significant_others', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('kids', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(
                'temp_people_id'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
