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

class MigrateEntriesObjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('journal_entries')
            ->where('journalable_type', 'App\Activity')
            ->update(['journalable_type' => 'App\Models\Contact\Activity']);

        DB::table('journal_entries')
            ->where('journalable_type', 'App\Day')
            ->update(['journalable_type' => 'App\Models\Journal\Day']);

        DB::table('journal_entries')
            ->where('journalable_type', 'App\Entry')
            ->update(['journalable_type' => 'App\Models\Journal\Entry']);
    }
}
