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
        Schema::create('marketplace_listing_versions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('marketplace_listing_id')->comment('listing this is a version of');
            $table->unsignedInteger('version_number')->comment('incrementing number within the listing, starting at 1');
            $table->json('payload')->comment('the whole structure, self contained, with its pages, section placements, fields and value lists');
            $table->text('change_note')->nullable()->comment('what changed since the previous version, shown to every installer');
            $table->string('status', 32)->comment('where it is in moderation');
            $table->text('rejection_reason')->nullable()->comment('why it was refused');
            $table->timestamp('approved_at')->nullable()->comment('when it was approved, null while nobody has looked at it');
            $table->timestamp('withdrawn_at')->nullable()->comment('when it was pulled, null while it is still offered');
            $table->unsignedBigInteger('previous_version_id')->nullable()->comment('version it was built from, null for the first one');
            $table->unsignedInteger('install_count')->default(0)->comment('how many accounts installed this precise version');
            $table->string('checksum', 64)->comment('hash of the payload, so an install can prove it got what was published');
            $table->timestamps();

            $table->unique(['marketplace_listing_id', 'version_number'], 'marketplace_listing_versions_number_unique');

            $table->foreign('marketplace_listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->foreign('previous_version_id')->references('id')->on('marketplace_listing_versions')->nullOnDelete();
        });

        Schema::table('marketplace_listings', function (Blueprint $table): void {
            $table->foreign('published_version_id')->references('id')->on('marketplace_listing_versions')->nullOnDelete();
        });
    }
};
