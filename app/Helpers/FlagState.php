<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;
use Laravel\Pennant\Feature;

class FlagState
{
    /**
     * Whether a flag is on for an account. A visitor has no account, so a public page behind a
     * flag shows them what every account sees.
     */
    public static function isOn(string $flag, ?Account $account): bool
    {
        if ($account === null) {
            return Feature::for(null)->active($flag);
        }

        return $account->features()->active($flag);
    }
}
