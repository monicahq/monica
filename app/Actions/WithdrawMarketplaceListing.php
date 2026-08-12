<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ListingIsWithdrawn;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take a listing out of the catalogue.
 *
 * It stops being offered and its published version stops being installable.
 * Everybody who already installed it keeps exactly what they have, untouched
 * and unaware, because what they hold is their own copy and never a link back
 * here. Their installations are deliberately left alone.
 *
 * The listing is not deleted and its slug is not released. A withdrawn listing
 * is the answer to why an address people had stopped working, and handing that
 * address to somebody else's work would be worse than a dead link.
 */
class WithdrawMarketplaceListing
{
    public function __construct(
        private readonly User $user,
        private readonly MarketplaceListing $listing,
    ) {}

    public function execute(): MarketplaceListing
    {
        $this->validate();
        $this->withdraw();
        $this->log();

        return $this->listing;
    }

    private function validate(): void
    {
        if ($this->listing->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This listing is in another account.');
        }

        if ($this->listing->status === MarketplaceListingStatusEnum::Withdrawn) {
            throw new ListingIsWithdrawn("{$this->listing->name} has already been taken out of the marketplace.");
        }
    }

    /**
     * The tally is guarded rather than decremented outright. It is a count kept
     * beside the thing it counts, so it can drift, and the column is unsigned:
     * taking one off a category already at zero would refuse the write and take
     * the whole withdrawal down with it. Somebody pulling their own work is not
     * the person who should discover a counter is off.
     */
    private function withdraw(): void
    {
        DB::transaction(function (): void {
            $wasPublic = $this->listing->status->isPublic();
            $publishedVersion = $this->listing->publishedVersion;

            if ($publishedVersion !== null) {
                $publishedVersion->status = MarketplaceVersionStatusEnum::Withdrawn;
                $publishedVersion->withdrawn_at = now();
                $publishedVersion->save();
            }

            $this->listing->status = MarketplaceListingStatusEnum::Withdrawn;
            $this->listing->published_version_id = null;
            $this->listing->save();

            if ($wasPublic) {
                $this->listing->category()->where('listing_count', '>', 0)->decrement('listing_count');
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::MarketplaceListingWithdrawal,
            parameters: ['name' => $this->listing->name],
            loggable: $this->listing,
        )->onQueue('low');
    }
}
