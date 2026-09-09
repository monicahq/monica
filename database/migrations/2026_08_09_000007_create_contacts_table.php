<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs, share links and by the API');
            $table->unsignedBigInteger('account_id')->comment('account the contact belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault the contact lives in');
            $table->unsignedBigInteger('template_version_id')->comment('exact structure version the page renders from');
            $table->string('prefix', 50)->nullable()->comment('civility, such as Dr or Mrs');
            $table->string('first_name')->nullable()->comment('first name');
            $table->string('middle_name')->nullable()->comment('middle name');
            $table->string('last_name')->nullable()->comment('last name');
            $table->string('suffix', 50)->nullable()->comment('suffix, such as Jr or PhD');
            $table->string('maiden_name')->nullable()->comment('name before marriage, kept because searching on it is common');
            $table->string('nickname')->nullable()->comment('what they are actually called');
            $table->string('name_order', 32)->nullable()->comment('order this particular name reads in, null to follow the user preference');
            $table->string('sort_name', 511)->comment('precomputed string the list sorts on, so sorting never depends on rendering');
            $table->binary('sort_key', 2048)->comment('collation key the list orders and pages on, built for the language of the account, compared byte by byte so the order reads the same on every database');
            $table->string('search_key', 511)->comment('sort_name folded for the language of the account, so a search folds what that language calls a minor difference and nothing else');
            $table->boolean('is_me')->default(false)->comment('whether this contact represents the user themselves');
            $table->boolean('is_partial')->default(false)->comment('whether the contact came from a mention and is not filled in yet');
            $table->boolean('is_archived')->default(false)->comment('whether the contact is hidden from the main lists without being deleted');
            $table->boolean('is_favourite')->default(false)->comment('whether the contact is pinned to the top of the list');
            $table->unsignedBigInteger('avatar_file_id')->nullable()->comment('uploaded avatar, null when initials are used instead');
            $table->string('avatar_colour', 7)->comment('background colour of the generated initials avatar');
            $table->timestamp('last_activity_at')->nullable()->comment('when the last activity counting as a real interaction happened, null when none has');
            $table->unsignedInteger('activity_count')->default(0)->comment('running count of activities the contact took part in');
            $table->timestamp('last_consulted_at')->nullable()->comment('when the user last opened the page, used to order recently viewed');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vault_id', 'sort_name']);
            $table->index(['vault_id', 'sort_key']);
            $table->index(['vault_id', 'created_at']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('template_version_id')->references('id')->on('template_versions');
            $table->foreign('avatar_file_id')->references('id')->on('files')->nullOnDelete();
        });
    }
};
