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
            $table->uuid()->unique()->comment('unguessable identifier used in URLs, share links and by the API, and the one that never changes');
            $table->string('slug', 128)->comment('readable identifier used in URLs and by the API, built from the displayed name, unique within the vault');
            $table->unsignedBigInteger('account_id')->comment('account the contact belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault the contact lives in');
            $table->unsignedBigInteger('structure_version_id')->comment('exact structure version the page renders from');
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
            $table->dateTime('last_life_event_at')->nullable()->comment('date of the latest life event the contact participated in, null when none exists');
            $table->dateTime('last_interaction_at')->nullable()->comment('date of the latest life event the contact and the Me contact both took part in, null when none exists');
            $table->unsignedInteger('life_event_count')->default(0)->comment('running count of life events the contact took part in');
            $table->timestamp('last_consulted_at')->nullable()->comment('when the user last opened the page, used to order recently viewed');
            $table->string('dav_uri')->nullable()->comment('the .vcf name a device chose when it created the card, null for a contact made here, whose card is its uuid');
            $table->longText('vcard')->nullable()->comment('the card as a device last sent it, kept whole so nothing it wrote is lost, read by nothing but the merge');
            $table->text('search_name')->nullable()->comment('the name the contact is listed under, as the database search engine reads it');
            $table->mediumText('search_body')->nullable()->comment('every value that is not sensitive, as the owner reads it, for the database search engine');
            $table->mediumText('search_sensitive_body')->nullable()->comment('every sensitive value, for the database search engine, searched only for a caller who may read them');
            $table->text('search_linked_names')->nullable()->comment('the names of the people related to the contact, for the database search engine');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vault_id', 'sort_name']);
            $table->index(['vault_id', 'sort_key']);
            $table->index(['vault_id', 'created_at']);
            $table->unique(['vault_id', 'dav_uri']);
            $table->unique(['vault_id', 'slug']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('structure_version_id')->references('id')->on('structure_versions');
            $table->foreign('avatar_file_id')->references('id')->on('files')->nullOnDelete();

            if (config('scout.full_text_index') && in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
                $table->fullText(['search_name', 'search_body', 'search_sensitive_body', 'search_linked_names'], 'contacts_search_fulltext')->language('simple');
                $table->fullText(['search_name', 'search_body', 'search_linked_names'], 'contacts_search_without_sensitive_fulltext')->language('simple');
            }
        });
    }
};
