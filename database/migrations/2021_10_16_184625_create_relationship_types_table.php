<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelationshipTypesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('relationship_group_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('relationship_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('relationship_group_type_id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('name_reverse_relationship');
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
            $table->foreign('relationship_group_type_id')->references('id')->on('relationship_group_types')->onDelete('cascade');
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('relationship_type_id');
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('related_contact_id');
            $table->timestamps();
            $table->foreign('relationship_type_id')->references('id')->on('relationship_types')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('related_contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('relationship_group_types');
        Schema::dropIfExists('relationship_types');
        Schema::dropIfExists('relationships');
    }
}
