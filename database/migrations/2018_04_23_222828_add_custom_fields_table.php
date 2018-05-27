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
    {Schema::create('custom_field_patterns', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');
            $table->string('icon_name');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('custom_field_pattern_id');
            $table->string('name');
            $table->string('fields_order')->nullable();
            $table->boolean('is_list')->default(0);
            $table->boolean('is_important')->default(0);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('custom_field_pattern_id')->references('id')->on('custom_field_patterns')->onDelete('cascade');
        });

        Schema::create('fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('custom_field_id');
            $table->string('custom_field_type');
            $table->string('name');
            $table->string('description');
            $table->boolean('required')->default(0);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('default_custom_field_type_id')->references('id')->on('default_custom_field_types')->onDelete('cascade');
        });

        Schema::create('field_choices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('field_id');
            $table->string('value');
            $table->boolean('is_default')->default(0);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade');
        });

        Schema::create('contact_custom_field_patterns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('custom_field_pattern_id');
            $table->integer('contact_id');
            $table->timestamps();
        });

        Schema::create('contact_field_values', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('field_id');
            $table->mediumText('value');
            $table->timestamps();
        });
    }
}
