<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('days', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->date('date');
            $table->integer('rate');
            $table->mediumText('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->dateTime('date');
            $table->integer('journalable_id');
            $table->string('journalable_type');
            $table->timestamps();
        });
    }
}
