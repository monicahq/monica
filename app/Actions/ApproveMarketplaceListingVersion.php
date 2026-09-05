<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Jobs\LogAdministratorAction;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use App\Notifications\MarketplaceListingApproved;
use Illuminate\Support\Facades\DB;

/**
 * Let a version into the catalogue.
 */
class ApproveMarketplaceListingVersion
{
    public function __construct(
        private readonly MarketplaceListingVersion $version,
        private readonly User $administrator,
    ) {}

    public function execute(): MarketplaceListingVersion
    {
        DB::transaction(function (): void {
            $this->lock();
            $this->validate();
            $this->approve();
        });

        $this->tellTheAuthor();
        $this->log();

        return $this->version;
    }

    private function lock(): void
    {
        MarketplaceListingVersion::query()
            ->whereKey($this->version->getKey())
            ->lockForUpdate()
            ->first();

        $this->version->refresh();
    }

    private function validate(): void
    {
        if (! $this->version->status->awaitsModeration()) {
            throw new ListingIsNotAwaitingModeration("Version {$this->version->version_number} is {$this->version->status->value} and nobody is waiting on a decision about it.");
        }
    }

    private function approve(): void
    {
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
        $listing->moderated_by = $this->administrator->name;
        $listing->first_published_at = $listing->first_published_at ?? now();
        $listing->save();

        if ($isFirst) {
            $listing->category()->increment('listing_count');
        }
    }

    private function tellTheAuthor(): void
    {
        $listing = $this->version->listing;
        $author = $listing->account->user;

        if ($author === null) {
            return;
        }

        $author->notify(new MarketplaceListingApproved(
            listingName: $listing->name,
            versionNumber: $this->version->version_number,
            url: route('marketplace.index'),
        ));
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::MarketplaceVersionApproved,
            account: $this->version->listing->account,
            parameters: [
                'version' => $this->version->version_number,
                'name' => $this->version->listing->name,
            ],
            subject: $this->version,
        )->onQueue('low');
    }
}
