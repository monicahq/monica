<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StayInTouch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('stay_in_touch_frequency')->nullable()->after('last_talked_to');
            $table->datetime('stay_in_touch_trigger_date')->nullable()->after('stay_in_touch_frequency');
        });
    }
}
