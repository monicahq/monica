<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ChangeEventsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $events = DB::table('events')
                            ->where('object_type', 'significantother')
                            ->get();

        foreach ($events as $event) {
            DB::table('events')->where('id', $event->id)->delete();
        }

        $events = DB::table('events')
                            ->where('object_type', 'kid')
                            ->get();

        foreach ($events as $event) {
            DB::table('events')->where('id', $event->id)->delete();
        }
    }
}
