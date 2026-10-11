<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\Account;
use Laravel\Pennant\Attributes\Name;
use Laravel\Pennant\Feature;

#[Name('mcp-server')]
class McpServer
{
    /**
     * On for every account until the administrator switches it off. The null scope stands for
     * every account, and answering it here is what makes the flag ship switched on.
     */
    public function resolve(?Account $account): bool
    {
        if ($account === null) {
            return true;
        }

        return Feature::for(null)->active(self::class);
    }
}
