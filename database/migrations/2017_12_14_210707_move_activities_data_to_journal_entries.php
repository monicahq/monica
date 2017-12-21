<?php

use Illuminate\Database\Migrations\Migration;

class MoveActivitiesDataToJournalEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $activities = DB::table('activities')->select('account_id', 'id', 'date_it_happened', 'created_at', 'updated_at')->get();

        foreach ($activities as $activity) {
            $id = DB::table('journal_entries')->insertGetId([
                'account_id' => $activity->account_id,
                'date' => $activity->date_it_happened,
                'journalable_type' => 'App\Activity',
                'journalable_id' => $activity->id,
                'created_at' => $activity->created_at,
                'updated_at' => $activity->updated_at,
            ]);
        }
    }
}
