<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Models\MarketplaceListingVersion;
use Illuminate\Pagination\Paginator;

/**
 * What the moderation queue shows: every marketplace submission nobody has
 * decided about yet, oldest first.
 *
 * Oldest first is the whole point of the order. A queue sorted newest first is a
 * queue where the submission that has waited longest is the one nobody sees, and
 * an author waiting three weeks for an answer is the failure this screen exists
 * to remove.
 */
class ModerationQueueViewModel
{
    /**
     * How many submissions a page holds. Moderation is read one at a time, so
     * the page is small enough to take in at once.
     */
    private const int PER_PAGE = 20;

    /**
     * @return Paginator<int, MarketplaceListingVersion>
     */
    public function submissions(): Paginator
    {
        return once(fn (): Paginator => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->with(['listing.category', 'listing.account.user'])
            ->oldest()
            ->simplePaginate(self::PER_PAGE));
    }

    /**
     * How many submissions are waiting, which the overview also asks for and the
     * heading of this screen says out loud.
     */
    public function waitingCount(): int
    {
        return once(fn (): int => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->count());
    }
}
