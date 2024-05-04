<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Account;
use Illuminate\Contracts\Config\Repository as Config;

class SignupHelper
{
    public function __construct(
        protected Config $config
    )
    {  }

    public function isEnabled(): bool
    {
        return !($this->isDisabledByConfig() && $this->hasAtLeastOneAccount());
    }

    protected function isDisabledByConfig(): bool
    {
        return (bool) $this->config->get('monica.disable_signup');
    }

    protected function hasAtLeastOneAccount(): bool
    {
        return !empty(Account::first());
    }
}
