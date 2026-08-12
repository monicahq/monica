<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Helpers\TextSanitizer;
use App\Models\MarketplaceListingVersion;
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
        private string $moderator,
        private string $reason,
    ) {}

    public function execute(): MarketplaceListingVersion
    {
        $this->sanitize();
        $this->validate();
        $this->reject();

        return $this->version;
    }

    private function sanitize(): void
    {
        $this->moderator = TextSanitizer::plainText($this->moderator);
        $this->reason = TextSanitizer::plainText($this->reason);
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
        DB::transaction(function (): void {
            $this->version->status = MarketplaceVersionStatusEnum::Rejected;
            $this->version->rejection_reason = $this->reason;
            $this->version->save();

            $listing = $this->version->listing;

            $listing->status = $listing->published_version_id === null
                ? MarketplaceListingStatusEnum::Rejected
                : MarketplaceListingStatusEnum::Published;
            $listing->rejection_reason = $this->reason;
            $listing->moderated_at = now();
            $listing->moderated_by = $this->moderator;
            $listing->save();
        });
    }
}
