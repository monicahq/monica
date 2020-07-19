<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomLifeEventTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('life_event_types', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        DB::table('life_event_types')
            ->update(['name' => null]);
    }
}
