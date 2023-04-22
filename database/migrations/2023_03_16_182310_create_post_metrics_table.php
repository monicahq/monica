<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->string('label');
            $table->timestamps();
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
        });

        Schema::create('post_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('journal_metric_id');
            $table->integer('value');
            $table->string('label')->nullable();
            $table->timestamps();
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('journal_metric_id')->references('id')->on('journal_metrics')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_metrics');
        Schema::dropIfExists('journal_metrics');
    }
};
