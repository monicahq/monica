<?php

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
