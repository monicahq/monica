<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWebauthn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webauthn_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');

            $table->string('name')->default('key');
            $table->string('credentialId', 255);
            $table->string('type', 255);
            $table->text('transports');
            $table->string('attestationType', 255);
            $table->text('trustPath');
            $table->text('aaguid');
            $table->text('credentialPublicKey');
            $table->integer('counter');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('credentialId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webauthn');
    }
}
