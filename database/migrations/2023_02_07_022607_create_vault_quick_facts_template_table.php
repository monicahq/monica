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
        Schema::create('vault_quick_facts_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vault_id');
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position');
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
        });

        Schema::create('quick_facts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vault_quick_facts_template_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('content');
            $table->timestamps();
            $table->foreign('vault_quick_facts_template_id')->references('id')->on('vault_quick_facts_templates')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('show_quick_facts')->default(false)->after('can_be_deleted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vault_quick_facts_templates');
        Schema::dropIfExists('quick_facts');
    }
};
