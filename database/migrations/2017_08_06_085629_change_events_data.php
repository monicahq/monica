<?php

use App\Event;
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
        $events = Event::where('object_type', 'significantother')->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}
