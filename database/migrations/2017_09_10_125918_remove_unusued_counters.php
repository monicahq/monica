<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUnusuedCounters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'number_of_notes',
                'number_of_activities',
                'number_of_gifts_ideas',
                'number_of_gifts_received',
                'number_of_gifts_offered',
                'number_of_tasks_in_progress',
                'number_of_tasks_completed'
            );
        });
    }
}
