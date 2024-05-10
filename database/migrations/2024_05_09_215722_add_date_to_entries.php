<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('entries', function (Blueprint $table) {
            // Add the new date column
            $table->date('date')->after('account_id');
        });

        // Copy the date from journal_entries table and populate the new column
        DB::table('entries')
            ->join('journal_entries', function ($join) {
                $join->on('entries.id', '=', 'journal_entries.journalable_id')
                    ->where('journal_entries.journalable_type', '=', 'App\Models\Journal\Entry');
            })
            ->update(['entries.date' => DB::raw('journal_entries.date')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
