<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelationshipTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_relationship_type_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('delible')->default(0);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        Schema::create('default_relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->integer('relationship_type_group_id');
            $table->boolean('delible')->default(0);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        // Create new table structure
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->integer('relationship_type_group_id');
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });

        Schema::create('relationship_type_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });

        Schema::create('temp_relationships_table', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('relationship_type_id');
            $table->integer('contact_is');
            $table->string('relationship_type_name');
            $table->integer('of_contact');
            $table->timestamps();
        });
    }
}
