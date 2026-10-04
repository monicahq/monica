<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Structure;
use App\Models\StructureVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_versions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the version belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Structure::class)->comment('structure this is a version of')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number')->comment('incrementing number within the structure, starting at 1');
            $table->string('status', 32)->comment('draft is editable, published is frozen, retired is no longer offered');
            $table->text('change_note')->nullable()->comment('what changed since the previous version, shown to anybody deciding whether to move');
            $table->timestamp('published_at')->nullable()->comment('when it was frozen, null while it is a draft');
            $table->timestamp('retired_at')->nullable()->comment('when it stopped being offered, null while it is still offered');
            $table->foreignIdFor(StructureVersion::class, 'previous_version_id')->nullable()->comment('version it was created from, null for the first one')->constrained()->nullOnDelete();
            $table->unsignedInteger('record_count')->default(0)->comment('how many records currently point at it, so the cost of retiring it is visible');
            $table->timestamps();

            $table->unique(['structure_id', 'version_number']);
        });

        Schema::table('structures', function (Blueprint $table): void {
            $table->foreignIdFor(StructureVersion::class, 'published_version_id')->nullable()->after('entity_type_id')->comment('the version a new record is given, null while only drafts exist')->constrained()->nullOnDelete();
        });
    }
};
