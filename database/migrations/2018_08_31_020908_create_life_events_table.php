<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLifeEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('life_event_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('life_event_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('life_event_category_id');
            $table->string('name');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('life_event_category_id')->references('id')->on('life_event_categories')->onDelete('cascade');
        });

        Schema::create('life_events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('life_event_type_id');
            $table->string('name');
            $table->mediumText('note');
            $table->dateTime('last_triggered')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('life_event_type_id')->references('id')->on('life_event_types')->onDelete('cascade');
        });

        Schema::create('life_events_contact', function (Blueprint $table) {
            $table->unsignedInteger('life_event_id');
            $table->unsignedInteger('contact_id');
            $table->foreign('life_event_id')->references('id')->on('life_events')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('life_events');
        Schema::dropIfExists('life_event_categories');
    }
}
