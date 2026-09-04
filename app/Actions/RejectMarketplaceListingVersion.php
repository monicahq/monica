<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Helpers\TextSanitizer;
use App\Jobs\LogAdministratorAction;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use App\Notifications\MarketplaceListingRejected;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Refuse a version, and say why.
 *
 * The reason is not optional. A refusal without one tells the author nothing
 * they can act on, and the specification asks for refusals to be explained and
 * communicated rather than silent.
 *
 * What happens to the listing depends on whether it had already made it in. One
 * that never did is refused with the version, and the author sees the reason on
 * it. One already in the catalogue stays exactly where it is, on the version
 * people are installing: a bad fifth version is no reason to take away a good
 * fourth one from everybody looking at it.
 */
class RejectMarketplaceListingVersion
{
    public function __construct(
        private readonly MarketplaceListingVersion $version,
        private readonly User $administrator,
        private string $reason,
    ) {}

    public function execute(): MarketplaceListingVersion
    {
        $this->sanitize();

        DB::transaction(function (): void {
            $this->lock();
            $this->validate();
            $this->reject();
        });

        $this->tellTheAuthor();
        $this->log();

        return $this->version;
    }

    private function sanitize(): void
    {
        $this->reason = TextSanitizer::plainText($this->reason);
    }

    /**
     * Hold the row for the rest of the transaction, then read back what it
     * actually says, so two administrators deciding at the same moment produce
     * exactly one decision.
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

        if ($this->reason === '') {
            throw new InvalidArgumentException('A refusal has to say why, since the author is the one who has to act on it.');
        }
    }

    private function reject(): void
    {
        $this->version->status = MarketplaceVersionStatusEnum::Rejected;
        $this->version->rejection_reason = $this->reason;
        $this->version->save();

        $listing = $this->version->listing;

        $listing->status = $listing->published_version_id === null
            ? MarketplaceListingStatusEnum::Rejected
            : MarketplaceListingStatusEnum::Published;
        $listing->rejection_reason = $this->reason;
        $listing->moderated_at = now();
        $listing->moderated_by = $this->administrator->name;
        $listing->save();
    }

    /**
     * An account whose person was deleted has nobody to tell. The reason stays
     * on the version either way, which is where the author would read it.
     */
    private function tellTheAuthor(): void
    {
        $listing = $this->version->listing;
        $author = $listing->account->user;

        if ($author === null) {
            return;
        }

        $author->notify(new MarketplaceListingRejected(
            listingName: $listing->name,
            versionNumber: $this->version->version_number,
            reason: $this->reason,
        ));
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::MarketplaceVersionRejected,
            account: $this->version->listing->account,
            parameters: [
                'version' => $this->version->version_number,
                'name' => $this->version->listing->name,
            ],
            subject: $this->version,
        )->onQueue('low');
    }
}
