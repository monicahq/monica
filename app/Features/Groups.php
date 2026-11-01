<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Account;
use Laravel\Pennant\Attributes\Name;
use Laravel\Pennant\Feature;

#[Name('groups')]
class Groups
{
    public function resolve(Account $account): bool
    {
        return Feature::for(null)->active(self::class);
    }
}
