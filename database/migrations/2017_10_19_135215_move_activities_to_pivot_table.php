<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class MoveActivitiesToPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Select all activities
        $activities = DB::table('activities')->select('id', 'contact_id')->get();

        foreach ($activities as $activity) {
            DB::table('activity_contact')->insert(
                ['contact_id' => $activity->contact_id, 'activity_id' => $activity->id]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('activity_contact')->truncate();
    }
}
