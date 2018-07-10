<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateU2fKeyTable.
 *
 *
 *
 * @author  LAHAXE Arnaud
 */
class CreateU2fKeyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('u2f_key', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('keyHandle');
            $table->string('publicKey')->unique();
            $table->text('certificate');
            $table->integer('counter');
            $table->timestamps();
        });

        Schema::table('u2f_key', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('u2f_key');
    }
}
