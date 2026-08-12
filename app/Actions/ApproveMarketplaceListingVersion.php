<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Helpers\TextSanitizer;
use App\Models\MarketplaceListingVersion;
use Illuminate\Support\Facades\DB;

/**
 * Let a version into the catalogue.
 *
 * From here the payload never changes again, which is what makes the checksum
 * an install verifies worth anything, and it is the version everybody
 * installing from now on receives.
 *
 * Approving a later version does not touch anybody who installed an earlier
 * one. They are told, and they decide. That is the whole of it, and it is the
 * rule the rest of the marketplace is built to keep.
 *
 * There is no user here. Moderation happens on our side, and who took the
 * decision is written down as a name rather than as a row in a table of people
 * who have accounts, because the two are not the same thing.
 */
class ApproveMarketplaceListingVersion
{
    public function __construct(
        private readonly MarketplaceListingVersion $version,
        private string $moderator,
    ) {}

    public function execute(): MarketplaceListingVersion
    {
        $this->sanitize();
        $this->validate();
        $this->approve();

        return $this->version;
    }

    private function sanitize(): void
    {
        $this->moderator = TextSanitizer::plainText($this->moderator);
    }

    private function validate(): void
    {
        if (! $this->version->status->awaitsModeration()) {
            throw new ListingIsNotAwaitingModeration("Version {$this->version->version_number} is {$this->version->status->value} and nobody is waiting on a decision about it.");
        }
    }

    private function approve(): void
    {
        DB::transaction(function (): void {
            $this->version->status = MarketplaceVersionStatusEnum::Approved;
            $this->version->approved_at = now();
            $this->version->rejection_reason = null;
            $this->version->save();

            $listing = $this->version->listing;
            $isFirst = $listing->first_published_at === null;

            $listing->status = MarketplaceListingStatusEnum::Published;
            $listing->published_version_id = $this->version->id;
            $listing->rejection_reason = null;
            $listing->moderated_at = now();
            $listing->moderated_by = $this->moderator;
            $listing->first_published_at = $listing->first_published_at ?? now();
            $listing->save();

            if ($isFirst) {
                $listing->category()->increment('listing_count');
            }
        });
    }
}
