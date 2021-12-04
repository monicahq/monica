<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlacesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('placeable_id')->nullable();
            $table->string('placeable_type')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->char('country', 3)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('address_type_id');
            $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('address_type_id')->references('id')->on('address_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('places');
        Schema::dropIfExists('contact_addresses');
    }
}
