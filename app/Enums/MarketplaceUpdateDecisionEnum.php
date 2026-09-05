<?php

declare(strict_types=1);

namespace App\Enums;

enum MarketplaceUpdateDecisionEnum: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Declined = 'declined';
}
