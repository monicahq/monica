<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a listing stands on the publication path.
 *
 * The path is the one section 7 describes and nothing else. Something built in
 * an account is private, and it stays private until its author deliberately
 * submits it. A draft is that deliberate act not yet taken. Moderation happens
 * between the submission and the catalogue, and a refusal is a state of its own
 * rather than a silent return to the draft, because the author is owed the
 * reason and the reason has to sit somewhere they can read it.
 *
 * Withdrawn is the end of the road for the catalogue and changes nothing for
 * anybody who already installed it. What they hold is their own copy.
 */
enum MarketplaceListingStatusEnum: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Published = 'published';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /**
     * Whether the listing appears in the catalogue and can be installed.
     */
    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    /**
     * Whether somebody on our side still has a decision to take about it.
     */
    public function awaitsModeration(): bool
    {
        return $this === self::Submitted;
    }
}
