<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_version_sections', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the assembly belongs to');
            $table->unsignedBigInteger('template_version_id')->comment('template version the section is placed in');
            $table->unsignedBigInteger('section_version_id')->comment('exact section version placed, never the section itself');
            $table->unsignedInteger('position')->comment('order the section appears on the page');
            $table->string('title_override')->nullable()->comment('title to show instead of the section own name, null to use its own');
            $table->boolean('is_collapsed_by_default')->default(false)->comment('whether the section starts folded, so a long page stays readable');
            $table->timestamps();

            $table->unique(['template_version_id', 'section_version_id'], 'template_version_sections_unique');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('template_version_id')->references('id')->on('template_versions')->cascadeOnDelete();
            $table->foreign('section_version_id')->references('id')->on('section_versions')->cascadeOnDelete();
        });
    }
};
