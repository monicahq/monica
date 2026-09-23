<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_list_columns', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the column belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault whose list of people shows the column');
            $table->string('source', 32)->comment('what the column shows, a ContactListColumnSourceEnum value');
            $table->unsignedBigInteger('section_id')->nullable()->comment('section holding the field when source is field, null otherwise');
            $table->string('field_key', 64)->nullable()->comment('key of the field inside the section when source is field, null otherwise');
            $table->string('heading')->nullable()->comment('heading somebody wrote for the column, null means the default heading');
            $table->unsignedInteger('position')->comment('order of the column after the name, starting at 1');
            $table->boolean('is_visible')->default(true)->comment('whether the list shows the column');
            $table->timestamps();

            $table->unique(['vault_id', 'position']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
        });
    }
};
