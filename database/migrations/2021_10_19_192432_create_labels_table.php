<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vault_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('bg_color')->default('bg-zinc-200');
            $table->string('text_color')->default('text-zinc-700');
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
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
};
