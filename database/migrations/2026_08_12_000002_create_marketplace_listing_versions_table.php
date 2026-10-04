<?php

declare(strict_types=1);

use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listing_versions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(MarketplaceListing::class)->comment('listing this is a version of')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number')->comment('incrementing number within the listing, starting at 1');
            $table->json('payload')->comment('the whole structure, self contained, with its pages, section placements, fields and value lists');
            $table->text('change_note')->nullable()->comment('what changed since the previous version, shown to every installer');
            $table->string('status', 32)->comment('where it is in moderation');
            $table->text('rejection_reason')->nullable()->comment('why it was refused');
            $table->timestamp('approved_at')->nullable()->comment('when it was approved, null while nobody has looked at it');
            $table->timestamp('withdrawn_at')->nullable()->comment('when it was pulled, null while it is still offered');
            $table->foreignIdFor(MarketplaceListingVersion::class, 'previous_version_id')->nullable()->comment('version it was built from, null for the first one')->constrained()->nullOnDelete();
            $table->unsignedInteger('install_count')->default(0)->comment('how many accounts installed this precise version');
            $table->string('checksum', 64)->comment('hash of the payload, so an install can prove it got what was published');
            $table->timestamps();

            $table->unique(['marketplace_listing_id', 'version_number'], 'marketplace_listing_versions_number_unique');
        });

        Schema::table('marketplace_listings', function (Blueprint $table): void {
            $table->foreignIdFor(MarketplaceListingVersion::class, 'published_version_id')->nullable()->after('status')->comment('version currently offered for installation, null before the first is approved')->constrained()->nullOnDelete();
        });
    }
};
