<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketplaceVersionStatusEnum: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function isInstallable(): bool
    {
        return $this === self::Approved;
    }

    public function awaitsModeration(): bool
    {
        return $this === self::Submitted;
    }
}
