<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API, alongside the readable slug');
            $table->unsignedBigInteger('account_id')->comment('account the template belongs to');
            $table->string('applies_to', 32)->comment('what kind of record the template describes');
            $table->unsignedBigInteger('entity_type_id')->nullable()->comment('type it applies to when applies_to is entity, null otherwise');
            $table->string('name')->nullable()->comment('name the user gave it, null when it falls back to its translation key');
            $table->string('name_translation_key')->nullable()->comment('translation key of a supplied name');
            $table->text('description')->nullable()->comment('what the template is for, shown in the picker');
            $table->string('slug', 64)->comment('stable identifier used by the API, unique within the account');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it at account creation');
            $table->boolean('is_active')->default(true)->comment('whether it can still be applied to a new record');
            $table->unsignedBigInteger('published_version_id')->nullable()->comment('the version offered by default when applying it, null while only drafts exist');
            $table->string('locale', 12)->comment('language the labels inside it are written in');
            $table->unsignedInteger('position')->comment('order the templates appear in the picker');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'slug']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('entity_type_id')->references('id')->on('entity_types')->nullOnDelete();
        });
    }
};
