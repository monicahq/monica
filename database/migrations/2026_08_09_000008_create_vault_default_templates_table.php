<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_default_templates', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the default belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault the default applies in');
            $table->string('applies_to', 32)->comment('what kind of record the default is for');
            $table->unsignedBigInteger('entity_type_id')->nullable()->comment('type the default is for when applies_to is entity, null otherwise');
            $table->unsignedBigInteger('template_version_id')->comment('exact version a new record starts on');
            $table->timestamps();

            $table->unique(['vault_id', 'applies_to', 'entity_type_id'], 'vault_default_templates_unique');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('entity_type_id')->references('id')->on('entity_types')->cascadeOnDelete();
            $table->foreign('template_version_id')->references('id')->on('template_versions');
        });
    }
};
