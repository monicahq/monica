<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketplaceListingStatusEnum: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Published = 'published';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function awaitsModeration(): bool
    {
        return $this === self::Submitted;
    }
}
