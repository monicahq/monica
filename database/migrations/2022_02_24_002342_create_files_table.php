<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vault_id');
            $table->unsignedBigInteger('fileable_id')->nullable();
            $table->string('fileable_type')->nullable();
            $table->string('uuid');
            $table->string('original_url')->nullable();
            $table->string('cdn_url')->nullable();
            $table->string('mime_type');
            $table->string('name');
            $table->string('type');
            $table->integer('size');
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedBigInteger('file_id')->nullable()->after('company_id');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('file_id');
        });
    }
};
