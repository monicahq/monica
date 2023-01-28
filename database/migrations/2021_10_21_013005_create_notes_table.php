<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('vault_id');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedBigInteger('emotion_id')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('emotion_id')->references('id')->on('emotions')->onDelete('set null');

            if (config('scout.driver') === 'database' && in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql'])) {
                $table->fullText('title');
                $table->fullText('body');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('notes');
    }
};
