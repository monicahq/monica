<?php

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
    }
}
