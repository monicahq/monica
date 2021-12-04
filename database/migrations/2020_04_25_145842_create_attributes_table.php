<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttributesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('information', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->boolean('allows_multiple_entries')->default(false);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('information_id');
            $table->string('name');
            $table->string('unit')->nullable();
            $table->boolean('unit_placement_after')->default(true);
            $table->string('type');
            $table->boolean('has_default_value')->default(false);
            $table->timestamps();
            $table->foreign('information_id')->references('id')->on('information')->onDelete('cascade');
        });

        Schema::create('attribute_default_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('value');
            $table->timestamps();
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
        });

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('information_template', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('information_id');
            $table->integer('position');
            $table->timestamps();
            $table->foreign('information_id')->references('id')->on('information')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_default_values');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('attribute_template');
    }
}
