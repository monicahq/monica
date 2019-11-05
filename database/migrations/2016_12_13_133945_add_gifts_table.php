<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('people_id');
            $table->string('about_object_type')->nullable();
            $table->string('about_object_id')->nullable();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->longText('url')->nullable();
            $table->string('value_in_dollars')->nullable();
            $table->string('is_an_idea')->default('true');
            $table->string('has_been_offered')->default('false');
            $table->dateTime('date_offered')->nullable();
            $table->timestamps();
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
