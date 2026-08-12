<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where one version of a listing stands in moderation.
 *
 * It is deliberately not {@see VersionStatusEnum}. A template version is a
 * private matter between somebody and their own records, and it has a draft
 * state because designing takes several passes. A listing version has none: it
 * only exists once its author has decided to submit it, and what happens to it
 * afterwards is somebody else's decision.
 */
enum MarketplaceVersionStatusEnum: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /**
     * Whether the version may still be copied into an account. A withdrawn one
     * may not, and every copy already made of it stays exactly as it is.
     */
    public function isInstallable(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Whether somebody on our side still has a decision to take about it.
     */
    public function awaitsModeration(): bool
    {
        return $this === self::Submitted;
    }
}
