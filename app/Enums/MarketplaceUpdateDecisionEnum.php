<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What an installer decided about a newer version of something they installed.
 *
 * Declining is a real answer and not a postponement, which is why it is written
 * down rather than inferred from the absence of an update. It does not stop the
 * installer being told about the version after that one: what they refused is
 * this change, not every change to come.
 */
enum MarketplaceUpdateDecisionEnum: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Declined = 'declined';
}
