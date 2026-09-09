<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API, alongside the public slug');
            $table->unsignedBigInteger('account_id')->comment('account that published it');
            $table->unsignedBigInteger('marketplace_category_id')->comment('category it was filed under');
            $table->string('listable_type')->comment('class of what is published, a template or a section');
            $table->unsignedBigInteger('listable_id')->comment('id of that object in the author account');
            $table->string('slug', 128)->unique()->comment('stable identifier used in the public URL, unique across the catalogue');
            $table->string('name')->comment('name shown in the catalogue, which may differ from the internal name of the author');
            $table->string('summary')->comment('one line describing what it is for, shown on the card');
            $table->text('description')->comment('the full description, shown on the listing page');
            $table->string('locale', 12)->comment('language the labels inside it are written in, shown and filtered on');
            $table->string('author_name')->comment('name shown as the author, which need not be the real name of the account holder');
            $table->string('status', 32)->comment('where it is in the publication path');
            $table->unsignedBigInteger('published_version_id')->nullable()->comment('version currently offered for installation, null before the first is approved');
            $table->text('rejection_reason')->nullable()->comment('why it was refused, so the refusal is explained rather than bare');
            $table->timestamp('moderated_at')->nullable()->comment('when the decision was taken');
            $table->string('moderated_by')->nullable()->comment('who took it, on our side');
            $table->unsignedInteger('install_count')->default(0)->comment('how many accounts installed it, shown publicly as a quality signal');
            $table->timestamp('first_published_at')->nullable()->comment('when it first became public');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['listable_type', 'listable_id'], 'marketplace_listings_listable_index');
            $table->index(['marketplace_category_id', 'status']);
            $table->index(['status', 'updated_at']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('marketplace_category_id')->references('id')->on('marketplace_categories');
        });
    }
};
