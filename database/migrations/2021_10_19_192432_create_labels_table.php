<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLabelsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('contact_label', function (Blueprint $table) {
            $table->unsignedBigInteger('label_id');
            $table->unsignedBigInteger('contact_id');
            $table->timestamps();
            $table->foreign('label_id')->references('id')->on('labels')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('labels');
        Schema::dropIfExists('contact_label');
    }
}
