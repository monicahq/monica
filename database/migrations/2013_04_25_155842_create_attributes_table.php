<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('template_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('name');
            $table->string('slug');
            $table->integer('position')->nullable();
            $table->string('type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('reserved_to_contact_information')->default(false);
            $table->boolean('can_be_deleted')->default(true);
            $table->integer('pagination')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('module_template_page', function (Blueprint $table) {
            $table->unsignedBigInteger('template_page_id');
            $table->unsignedBigInteger('module_id');
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->foreign('template_page_id')->references('id')->on('template_pages')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });

        Schema::create('module_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });

        Schema::create('module_row_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_row_id');
            $table->string('label');
            $table->string('module_field_type');
            $table->boolean('required')->default(false);
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->foreign('module_row_id')->references('id')->on('module_rows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('templates');
        Schema::dropIfExists('template_pages');
        Schema::dropIfExists('module_template_page');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('module_rows');
        Schema::dropIfExists('module_row_fields');
    }
}
