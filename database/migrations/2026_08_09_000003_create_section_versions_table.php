<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_versions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the version belongs to');
            $table->unsignedBigInteger('section_id')->comment('section this is a version of');
            $table->unsignedInteger('version_number')->comment('incrementing number within the section, starting at 1');
            $table->string('status', 32)->comment('draft is editable, published is frozen, retired is no longer offered');
            $table->text('change_note')->nullable()->comment('what changed since the previous version');
            $table->json('resolved_value_lists')->nullable()->comment('snapshot of every value list the fields read from, as it stood at publication');
            $table->timestamp('published_at')->nullable()->comment('when it was frozen, null while it is a draft');
            $table->timestamp('retired_at')->nullable()->comment('when it stopped being offered');
            $table->unsignedBigInteger('previous_version_id')->nullable()->comment('version it was created from, null for the first one');
            $table->unsignedInteger('usage_count')->default(0)->comment('how many template versions contain it');
            $table->timestamps();

            $table->unique(['section_id', 'version_number']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
            $table->foreign('previous_version_id')->references('id')->on('section_versions')->nullOnDelete();
        });

        Schema::table('sections', function (Blueprint $table): void {
            $table->foreign('published_version_id')->references('id')->on('section_versions')->nullOnDelete();
        });
    }
};
