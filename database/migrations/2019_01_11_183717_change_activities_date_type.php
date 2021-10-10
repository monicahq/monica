<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
