<?php

declare(strict_types=1);

namespace Tests\Fixtures\Features;

use App\Models\Account;
use Laravel\Pennant\Attributes\Name;
use Laravel\Pennant\Feature;

#[Name('example-capability')]
class ExampleCapability
{
    public function resolve(Account $account): bool
    {
        return Feature::for(null)->active(self::class);
    }
}
