<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;

class SignupHelper
{
    public function isEnabled(): bool
    {
        return ! ($this->isDisabledByConfig() && $this->hasAtLeastOneAccount());
    }

    protected function isDisabledByConfig(): bool
    {
        return config('monica.disable_signup');
    }

    protected function hasAtLeastOneAccount(): bool
    {
        return ! empty(Account::first());
    }
}
