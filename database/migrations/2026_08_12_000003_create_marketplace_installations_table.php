<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_installations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account that installed it')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MarketplaceListing::class)->comment('listing it came from')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MarketplaceListingVersion::class)->comment('exact version that was copied')->constrained(indexName: 'marketplace_installations_version_foreign');
            $table->string('installable_type')->comment('class of the local copy, a template, a section or a life event type');
            $table->unsignedBigInteger('installable_id')->comment('id of that local copy');
            $table->timestamp('installed_at')->comment('when the copy was made');
            $table->boolean('has_local_changes')->default(false)->comment('whether the installer edited their copy since, which makes an update riskier');
            $table->foreignIdFor(MarketplaceListingVersion::class, 'update_available_version_id')->nullable()->comment('newer approved version, null when the copy is current')->constrained()->nullOnDelete();
            $table->timestamp('update_notified_at')->nullable()->comment('when the installer was told about it');
            $table->string('update_decision', 32)->nullable()->comment('what they decided, null when nothing new has appeared');
            $table->timestamp('update_decided_at')->nullable()->comment('when they decided');
            $table->json('value_list_mapping')->nullable()->comment('how the value lists of the payload were resolved locally, copied or merged');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['installable_type', 'installable_id'], 'marketplace_installations_installable_index');
            $table->index(['account_id', 'marketplace_listing_id'], 'marketplace_installations_account_listing_index');
        });
    }
};
