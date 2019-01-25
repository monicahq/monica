<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContactPhotoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_photo', function (Blueprint $table) {
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('photo_id');
            $table->timestamps();
            $table->foreign('photo_id')->references('id')->on('photos')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
