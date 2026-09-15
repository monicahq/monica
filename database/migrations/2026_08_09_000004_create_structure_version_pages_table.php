<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_version_pages', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API');
            $table->unsignedBigInteger('account_id')->comment('account the page belongs to');
            $table->unsignedBigInteger('structure_version_id')->comment('exact structure version containing the page');
            $table->string('key', 64)->comment('stable identifier within the structure version, used in record URLs and copied into later drafts');
            $table->string('label')->nullable()->comment('label shown in the tab, null when it falls back to its translation key');
            $table->string('label_translation_key')->nullable()->comment('translation key of a supplied label');
            $table->unsignedInteger('position')->comment('order the page appears in the structure version');
            $table->timestamps();

            $table->unique(['structure_version_id', 'key']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('structure_version_id')->references('id')->on('structure_versions')->cascadeOnDelete();
        });
    }
};
