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
        Schema::create('sections', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API, alongside the readable slug');
            $table->unsignedBigInteger('account_id')->comment('account the section belongs to');
            $table->string('name')->nullable()->comment('name the user gave it, null when it falls back to its translation key');
            $table->string('name_translation_key')->nullable()->comment('translation key of a supplied name');
            $table->text('description')->nullable()->comment('what the section is for, shown in the picker');
            $table->string('slug', 64)->comment('stable identifier used by the API, unique within the account');
            $table->string('icon', 64)->nullable()->comment('icon shown in the section header');
            $table->string('colour', 7)->nullable()->comment('hue the section header mark is drawn in, one of the vault colours, null for neutral');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it at account creation');
            $table->boolean('is_active')->default(true)->comment('whether it can still be added to a template');
            $table->unsignedBigInteger('published_version_id')->nullable()->comment('version offered by default when adding it, null while only drafts exist');
            $table->string('locale', 5)->comment('language the labels inside it are written in');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'slug']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }
};
