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
        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->string('type');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('default_template_id')->nullable();
            $table->boolean('show_activity_tab_on_dashboard')->default(true);
            $table->boolean('show_group_tab')->default(true);
            $table->boolean('show_tasks_tab')->default(true);
            $table->boolean('show_files_tab')->default(true);
            $table->boolean('show_journal_tab')->default(true);
            $table->boolean('show_companies_tab')->default(true);
            $table->boolean('show_reports_tab')->default(true);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('default_template_id')->references('id')->on('templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('vaults');
    }
};
