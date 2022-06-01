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
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('life_event_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->boolean('can_be_deleted')->default(false);
            $table->string('type')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('life_event_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('life_event_category_id');
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->boolean('can_be_deleted')->default(false);
            $table->string('type')->nullable();
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->foreign('life_event_category_id')->references('id')->on('life_event_categories')->onDelete('cascade');
        });

        Schema::create('contact_life_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('life_event_type_id');
            $table->string('summary');
            $table->date('started_at');
            $table->date('ended_at');
            $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('life_event_type_id')->references('id')->on('life_event_types')->onDelete('cascade');
        });

        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('label');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_type_id');
            $table->string('label');
            $table->timestamps();
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
        });

        Schema::create('contact_life_event_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('life_event_id');
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('emotion_id')->nullable();
            $table->string('summary');
            $table->text('description')->nullable();
            $table->date('happened_at');
            $table->string('period_of_day');
            $table->timestamps();
            $table->foreign('life_event_id')->references('id')->on('contact_life_events')->onDelete('cascade');
            $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            $table->foreign('emotion_id')->references('id')->on('emotions')->onDelete('set null');
        });

        Schema::create('contact_activity_participants', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('contact_activity_id');
            $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('contact_activity_id')->references('id')->on('contact_life_event_activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('life_event_categories');
        Schema::dropIfExists('life_event_types');
        Schema::dropIfExists('contact_life_events');
        Schema::dropIfExists('activity_types');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('contact_life_event_activities');
        Schema::dropIfExists('contact_activity_participants');
    }
};
