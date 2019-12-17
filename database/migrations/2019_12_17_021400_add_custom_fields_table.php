<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');
            $table->boolean('accept_multiple_values')->default(0);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('custom_field_id');
            $table->string('custom_field_type');
            $table->string('name');
            $table->string('description');
            $table->boolean('required')->default(0);
            $table->timestamps();
            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
        });

        Schema::create('field_choices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('field_id');
            $table->string('value');
            $table->boolean('is_default')->default(0);
            $table->timestamps();
            $table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade');
        });

        Schema::create('contact_field_values', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contact_id');
            $table->integer('field_id');
            $table->mediumText('value');
            $table->timestamps();
            $table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
