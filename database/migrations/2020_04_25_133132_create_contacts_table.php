<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vault_id');
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->unsignedBigInteger('pronoun_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('maiden_name')->nullable();
            $table->string('job_position')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->boolean('listed')->default(true);
            $table->datetime('last_updated_at')->nullable();
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('gender_id')->references('id')->on('genders')->onDelete('set null');
            $table->foreign('pronoun_id')->references('id')->on('pronouns')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });

        Schema::create('user_vault', function (Blueprint $table) {
            $table->unsignedBigInteger('vault_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->integer('permission');
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });

        Schema::create('contact_vault_user', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('vault_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('number_of_views');
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('user_vault');
        Schema::dropIfExists('contact_vault_user');
    }
}
