<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->unsignedBigInteger('account_id')->comment('account the entity belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault the entity lives in');
            $table->unsignedBigInteger('entity_type_id')->comment('type of object it is, which never changes');
            $table->unsignedBigInteger('structure_version_id')->comment('exact structure version the page renders from');
            $table->string('name')->comment('what the record is called, used in lists, headers and search');
            $table->string('sort_name', 511)->comment('precomputed string the list sorts on, so sorting never depends on rendering');
            $table->binary('sort_key', 2048)->comment('collation key the list orders and pages on, built for the language of the account, compared byte by byte so the order reads the same on every database');
            $table->string('search_key', 511)->comment('sort_name folded for the language of the account, so a search folds what that language calls a minor difference and nothing else');
            $table->unsignedBigInteger('avatar_file_id')->nullable()->comment('image standing in for it, null when a generated one is used');
            $table->string('avatar_colour', 7)->comment('background colour of the generated avatar');
            $table->boolean('is_archived')->default(false)->comment('whether it is hidden from the main lists without being deleted');
            $table->timestamp('last_consulted_at')->nullable()->comment('when the user last opened the page, used to order recently viewed');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('deleted_with_vault')->default(false)->comment('whether the entity was taken out by the deletion of its vault, so restoring the vault knows what to bring back');

            $table->index(['vault_id', 'sort_name']);
            $table->index(['vault_id', 'sort_key']);
            $table->index(['vault_id', 'entity_type_id']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('entity_type_id')->references('id')->on('entity_types');
            $table->foreign('structure_version_id')->references('id')->on('structure_versions');
            $table->foreign('avatar_file_id')->references('id')->on('files')->nullOnDelete();
        });
    }
};
