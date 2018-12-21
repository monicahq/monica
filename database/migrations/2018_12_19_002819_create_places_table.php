<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('places', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->char('country', 3)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedInteger('place_id')->nullable()->after('account_id');
            $table->foreign('place_id')->references('id')->on('places')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('places');
    }
}
