<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_version_sections', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the assembly belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(StructureVersion::class)->comment('structure version the section is placed in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(StructureVersionPage::class)->comment('page inside that structure version which contains the section')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SectionVersion::class)->comment('exact section version placed, never the section itself')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->comment('order the section appears inside its page');
            $table->string('title_override')->nullable()->comment('title to show instead of the section own name, null to use its own');
            $table->boolean('shows_title')->default(true)->comment('whether the section name is drawn above its fields, false for the block holding the fields that are in no group');
            $table->boolean('is_collapsed_by_default')->default(false)->comment('whether the section starts folded, so a long page stays readable');
            $table->timestamps();

            $table->unique(['structure_version_id', 'section_version_id'], 'structure_version_sections_unique');
        });
    }
};
