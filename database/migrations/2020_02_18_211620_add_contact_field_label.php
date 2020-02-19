<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContactFieldLabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_field_labels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('account_id');
            $table->string('label_i18n', 20)->nullable();
            $table->string('label', 500)->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('contact_field_contact_field_label', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_field_label_id');
            $table->unsignedInteger('contact_field_id');
            $table->unsignedInteger('account_id');
            $table->foreign('contact_field_label_id')->references('id')->on('contact_field_labels')->onDelete('cascade');
            $table->foreign('contact_field_id')->references('id')->on('contact_fields')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('address_contact_field_label', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_field_label_id');
            $table->unsignedInteger('address_id');
            $table->unsignedInteger('account_id');
            $table->foreign('contact_field_label_id')->references('id')->on('contact_field_labels')->onDelete('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('address_contact_field_label');
        Schema::dropIfExists('contact_field_contact_field_label');
        Schema::dropIfExists('contact_field_labels');
    }
}
