<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class U2fKeyName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('u2f_key', function (Blueprint $table) {
            $table->string('name')->after('id')->default('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('u2f_key', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
}
