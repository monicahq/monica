<?php

declare(strict_types=1);

namespace App\ViewModels\Admin;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Helpers\PageSize;
use App\Models\MarketplaceListingVersion;
use Illuminate\Pagination\LengthAwarePaginator;

class ModerationQueueViewModel
{
    public const int PER_PAGE = 20;

    public function __construct(
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    /** @return LengthAwarePaginator<int, MarketplaceListingVersion> */
    public function submissions(): LengthAwarePaginator
    {
        return once(fn (): LengthAwarePaginator => PageSize::paginate(
            MarketplaceListingVersion::query()
                ->where('status', MarketplaceVersionStatusEnum::Submitted)
                ->with(['listing.category', 'listing.account.user'])
                ->oldest(),
            $this->size,
        ));
    }

    public function waitingCount(): int
    {
        return once(fn (): int => MarketplaceListingVersion::query()
            ->where('status', MarketplaceVersionStatusEnum::Submitted)
            ->count());
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }
}
