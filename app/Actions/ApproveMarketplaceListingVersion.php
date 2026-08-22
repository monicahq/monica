<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\EmailType;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Jobs\LogAdministratorAction;
use App\Jobs\SendEmail;
use App\Mail\MarketplaceListingApproved;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
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
 * The listing keeps a name rather than a foreign key for who decided, because
 * the catalogue is public and a moderator is a name to the people reading it.
 * The administrator is still handed in whole: the record of what operators do
 * has to point at a person, and a name is not a person.
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

    /**
     * Hold the row for the rest of the transaction, then read back what it
     * actually says. Two administrators opening the same submission and
     * deciding a moment apart would otherwise both pass the check below, and
     * the second decision would quietly replace the first.
     */
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

    /**
     * An account whose person was deleted has nobody to tell. The decision
     * still stands, since the catalogue is not the author's to hold up.
     */
    private function tellTheAuthor(): void
    {
        $listing = $this->version->listing;
        $author = $listing->account->user;

        if ($author === null) {
            return;
        }

        SendEmail::dispatch(
            mailable: new MarketplaceListingApproved(
                listingName: $listing->name,
                versionNumber: $this->version->version_number,
                url: route('marketplace.index'),
            ),
            account: $listing->account,
            emailType: EmailType::MarketplaceListingApproved,
            user: $author,
        )->onQueue('high');
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
