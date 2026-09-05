<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CategoryIsNotActive;
use App\Exceptions\ListingIsWithdrawn;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Change what the catalogue says about a listing.
 */
class UpdateMarketplaceListing
{
    public function __construct(
        private readonly User $user,
        private readonly MarketplaceListing $listing,
        private readonly MarketplaceCategory $category,
        private string $name,
        private string $summary,
        private string $description,
        private string $authorName,
    ) {}

    public function execute(): MarketplaceListing
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->listing;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->summary = TextSanitizer::plainText($this->summary);
        $this->description = TextSanitizer::plainText($this->description);
        $this->authorName = TextSanitizer::plainText($this->authorName);
    }

    private function validate(): void
    {
        if ($this->listing->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This listing is in another account.');
        }

        if ($this->listing->status === MarketplaceListingStatusEnum::Withdrawn) {
            throw new ListingIsWithdrawn("{$this->listing->name} has been taken out of the marketplace.");
        }

        if ($this->category->id !== $this->listing->marketplace_category_id && ! $this->category->is_active) {
            throw new CategoryIsNotActive("Nothing new can be filed into {$this->category->name}.");
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            $this->refile();

            $this->listing->marketplace_category_id = $this->category->id;
            $this->listing->name = $this->name;
            $this->listing->summary = $this->summary;
            $this->listing->description = $this->description;
            $this->listing->author_name = $this->authorName;
            $this->listing->save();
        });
    }

    private function refile(): void
    {
        if ($this->category->id === $this->listing->marketplace_category_id) {
            return;
        }

        if (! $this->listing->status->isPublic()) {
            return;
        }

        $this->listing->category()->where('listing_count', '>', 0)->decrement('listing_count');
        $this->category->increment('listing_count');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::MarketplaceListingUpdate,
            parameters: ['name' => $this->listing->name],
            loggable: $this->listing,
        )->onQueue('low');
    }
}
