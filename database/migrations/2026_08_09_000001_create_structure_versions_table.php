<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_versions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the version belongs to');
            $table->unsignedBigInteger('structure_id')->comment('structure this is a version of');
            $table->unsignedInteger('version_number')->comment('incrementing number within the structure, starting at 1');
            $table->string('status', 32)->comment('draft is editable, published is frozen, retired is no longer offered');
            $table->text('change_note')->nullable()->comment('what changed since the previous version, shown to anybody deciding whether to move');
            $table->timestamp('published_at')->nullable()->comment('when it was frozen, null while it is a draft');
            $table->timestamp('retired_at')->nullable()->comment('when it stopped being offered, null while it is still offered');
            $table->unsignedBigInteger('previous_version_id')->nullable()->comment('version it was created from, null for the first one');
            $table->unsignedInteger('record_count')->default(0)->comment('how many records currently point at it, so the cost of retiring it is visible');
            $table->timestamps();

            $table->unique(['structure_id', 'version_number']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('structure_id')->references('id')->on('structures')->cascadeOnDelete();
            $table->foreign('previous_version_id')->references('id')->on('structure_versions')->nullOnDelete();
        });

        Schema::table('structures', function (Blueprint $table): void {
            $table->foreign('published_version_id')->references('id')->on('structure_versions')->nullOnDelete();
        });
    }
};
