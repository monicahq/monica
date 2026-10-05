<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Account;
use Laravel\Pennant\Attributes\Name;
use Laravel\Pennant\Feature;

#[Name('marketplace')]
class Marketplace
{
    public function resolve(Account $account): bool
    {
        return Feature::for(null)->active(self::class);
    }
}
