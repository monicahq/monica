<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Models\MarketplaceListingVersion;
use Illuminate\Pagination\Paginator;

class ModerationQueueViewModel
{
    private const int PER_PAGE = 20;

    /** @return Paginator<int, MarketplaceListingVersion> */
    public function submissions(): Paginator
    {
        return once(fn (): Paginator => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->with(['listing.category', 'listing.account.user'])
            ->oldest()
            ->simplePaginate(self::PER_PAGE));
    }

    public function waitingCount(): int
    {
        return once(fn (): int => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->count());
    }
}
