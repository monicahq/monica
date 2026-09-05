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
