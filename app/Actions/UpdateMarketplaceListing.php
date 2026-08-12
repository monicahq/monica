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
 *
 * Only the copy and the category, never the structure. What people install is
 * the payload of an approved version and is frozen; fixing a typo in a
 * description has nothing to do with it and must not need moderating again.
 *
 * Refiling under another category is allowed for the same reason: it changes
 * where somebody looks for the thing and not what they get.
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

    /**
     * Move the tally with the listing. Only a public listing counts towards a
     * category, so a draft changing category moves nothing.
     *
     * The column is unsigned and the count can drift, so the decrement is
     * guarded: refiling must not fail because a tally somewhere was already
     * wrong.
     */
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
