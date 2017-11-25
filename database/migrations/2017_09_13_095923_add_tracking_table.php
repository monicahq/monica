<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_usage', function (Blueprint $table) {
            $table->increments('id');
            $table->string('url');
            $table->string('method');
            $table->string('client_ip');
            $table->timestamps();
        });
    }
}
