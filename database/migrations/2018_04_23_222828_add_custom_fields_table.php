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
        Schema::create('custom_field_patterns', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->string('icon_name');
            $table->timestamps();
        });

        Schema::create('custom_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('custom_field_pattern_id');
            $table->string('name');
            $table->string('fields_order');
            $table->boolean('is_list')->default(0);
            $table->boolean('is_important')->default(0);
            $table->timestamps();
        });

        Schema::create('fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('custom_field_id');
            $table->integer('custom_field_type_id');
            $table->string('name');
            $table->boolean('required')->default(0);
            $table->timestamps();
        });

        Schema::create('default_custom_field_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('field_choices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('field_id');
            $table->string('value');
            $table->boolean('is_default')->default(0);
            $table->timestamps();
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

        DB::table('default_custom_field_types')->insert([
            'type' => 'text',
        ]);
        DB::table('default_custom_field_types')->insert([
            'type' => 'textarea',
        ]);
        DB::table('default_custom_field_types')->insert([
            'type' => 'date',
        ]);
    }
}
