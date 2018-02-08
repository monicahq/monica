<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class RemoveTypeFromNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('activity_type_id');
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('sticky');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
