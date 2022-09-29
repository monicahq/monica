<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('label');
            $table->integer('position');
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('post_template_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_template_id');
            $table->string('label');
            $table->integer('position');
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
            $table->foreign('post_template_id')->references('id')->on('post_templates')->onDelete('cascade');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->string('title');
            $table->text('content');
            $table->datetime('written_at');
            $table->timestamps();
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_templates');
        Schema::dropIfExists('post_template_sections');
        Schema::dropIfExists('posts');
    }
};
