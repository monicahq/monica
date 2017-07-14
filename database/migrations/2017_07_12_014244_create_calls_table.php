<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->dateTime('called_at');
            $table->mediumText('content')->nullable();
            $table->timestamps();
        });
    }
}
