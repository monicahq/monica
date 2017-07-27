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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateOldContent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Activity::unsetEventDispatcher();
        foreach (Activity::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }

        Schema::table('events', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Event::unsetEventDispatcher();
        foreach (Event::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }

        Schema::table('gifts', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Gift::unsetEventDispatcher();
        foreach (Gift::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }

        Schema::table('kids', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Kid::unsetEventDispatcher();
        foreach (Kid::all() as $object) {
            $object->temp_people_id = $object->child_of_people_id;
            $object->save();
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Note::unsetEventDispatcher();
        foreach (Note::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }

        Schema::table('reminders', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Reminder::unsetEventDispatcher();
        foreach (Reminder::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }

        Schema::table('significant_others', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        SignificantOther::unsetEventDispatcher();
        foreach (SignificantOther::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('temp_people_id');
        });

        Task::unsetEventDispatcher();
        foreach (Task::all() as $object) {
            $object->temp_people_id = $object->people_id;
            $object->save();
        }
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
