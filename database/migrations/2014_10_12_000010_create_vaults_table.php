<?php

use App\Models\Account;
use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('vaults', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignIdFor(Template::class, 'default_template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->string('default_activity_tab')->default('activity');
            $table->boolean('show_group_tab')->default(true);
            $table->boolean('show_tasks_tab')->default(true);
            $table->boolean('show_files_tab')->default(true);
            $table->boolean('show_journal_tab')->default(true);
            $table->boolean('show_companies_tab')->default(true);
            $table->boolean('show_reports_tab')->default(true);
            $table->boolean('show_calendar_tab')->default(true);
            $table->timestamps();
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
