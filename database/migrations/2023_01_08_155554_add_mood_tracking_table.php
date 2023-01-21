<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mood_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('mood_tracking_parameter_id');
            $table->datetime('rated_at');
            $table->text('note')->nullable();
            $table->integer('number_of_hours_slept')->nullable();
            $table->timestamps();
            $table->foreign('mood_tracking_parameter_id')->references('id')->on('mood_tracking_parameters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mood_tracking_events');
    }
};
