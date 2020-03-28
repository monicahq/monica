<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeActivitiesDateType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->date('date_it_happened')->change();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->renameColumn('date_it_happened', 'happened_at');
        });
    }
}
