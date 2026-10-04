<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Section;
use App\Models\SectionVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_versions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the version belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Section::class)->comment('section this is a version of')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number')->comment('incrementing number within the section, starting at 1');
            $table->string('status', 32)->comment('draft is editable, published is frozen, retired is no longer offered');
            $table->text('change_note')->nullable()->comment('what changed since the previous version');
            $table->json('resolved_value_lists')->nullable()->comment('snapshot of every value list the fields read from, as it stood at publication');
            $table->timestamp('published_at')->nullable()->comment('when it was frozen, null while it is a draft');
            $table->timestamp('retired_at')->nullable()->comment('when it stopped being offered');
            $table->foreignIdFor(SectionVersion::class, 'previous_version_id')->nullable()->comment('version it was created from, null for the first one')->constrained()->nullOnDelete();
            $table->unsignedInteger('usage_count')->default(0)->comment('how many structure versions contain it');
            $table->timestamps();

            $table->unique(['section_id', 'version_number']);
        });

        Schema::table('sections', function (Blueprint $table): void {
            $table->foreignIdFor(SectionVersion::class, 'published_version_id')->nullable()->after('is_active')->comment('version offered by default when adding it, null while only drafts exist')->constrained()->nullOnDelete();
        });
    }
};
