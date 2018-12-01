<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateJournalEntriesWithExistingActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $activities = DB::table('activities')->select('account_id', 'date_it_happened', 'id', 'created_at')->get();

        foreach ($activities as $activity) {
            $journalEntryID = DB::table('journal_entries')->insertGetId([
                'account_id' => $activity->account_id,
                'date' => $activity->date_it_happened,
                'journalable_id' => $activity->id,
                'journalable_type' => 'App\Models\Contact\Activity',
                'created_at' => $activity->created_at,
            ]);
        }

        $entries = DB::table('entries')->select('account_id', 'created_at', 'id')->get();

        foreach ($entries as $entry) {
            $journalEntryID = DB::table('journal_entries')->insertGetId([
                'account_id' => $entry->account_id,
                'date' => $entry->created_at,
                'journalable_id' => $entry->id,
                'journalable_type' => 'App\Models\Journal\Entry',
                'created_at' => $entry->created_at,
            ]);
        }
    }
}
